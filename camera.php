<?php
session_start();
include 'db.php';

// Assuming we have the user ID stored in session
$user_id = $_SESSION['user_id'];

// Check if the user is logged in, else redirect to login
if (!isset($user_id)) {
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Capture</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #000; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; width: 100vw; overflow: hidden; }
        .camera-container { position: relative; width: 100vw; height: 100vh; overflow: hidden; display: flex; justify-content: center; align-items: center; background: #222; }
        .top-line, .bottom-line { position: absolute; width: 100%; height: 150px; background: black; z-index: 20; }
        .top-line { top: 0; }
        .bottom-line { bottom: 0; height: 200px; display: flex; justify-content: center; align-items: center; z-index: 10; }
        .gridlines { position: absolute; width: 100%; height: 100%; pointer-events: none; display: grid; grid-template-columns: 1fr 1fr 1fr; grid-template-rows: 1fr 1fr 1fr; }
        .gridlines div { border: 2px solid rgba(255, 255, 255, 0.5); }
        video, img { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
        video { position: relative; z-index: 10; }
        canvas { display: none; }
        .capture-btn, .retake-btn, .confirm-btn, .undertime-btn, .overtime-btn { position: absolute; bottom: 60px; width: 90px; height: 90px; background: white; border-radius: 50%; border: 6px solid rgba(255, 255, 255, 0.5); cursor: pointer; transition: 0.3s; z-index: 20; }
        .capture-btn { left: 50%; transform: translateX(-50%); }
        .undertime-btn { left: 20%; }
        .overtime-btn { right: 20%; }
        .retake-btn { left: 20%; display: none; }
        .confirm-btn { right: 20%; display: none; }
        .capture-btn:active, .retake-btn:active, .confirm-btn:active, .undertime-btn:active, .overtime-btn:active { background: lightgray; }
    </style>
</head>
<body>
    <div class="camera-container">
        <div class="top-line"></div>
        <video id="video" autoplay></video>
        <div class="gridlines">
            <div></div><div></div><div></div>
            <div></div><div></div><div></div>
            <div></div><div></div><div></div>
        </div>
        <canvas id="canvas"></canvas>
        <img id="photo" style="display:none;">
        <button id="capture" class="capture-btn">Capture</button>
        <button id="undertime" class="undertime-btn">Undertime</button>
        <button id="overtime" class="overtime-btn">Overtime</button>
        <button id="retake" class="retake-btn">Retake</button>
        <button id="confirm" class="confirm-btn">Confirm</button>
        <div class="bottom-line"></div>
    </div>

    <script>
        const video = document.getElementById("video");
        const canvas = document.getElementById("canvas");
        const ctx = canvas.getContext("2d");
        const photo = document.getElementById("photo");
        const captureBtn = document.getElementById("capture");
        const retakeBtn = document.getElementById("retake");
        const confirmBtn = document.getElementById("confirm");
        const undertimeBtn = document.getElementById("undertime");
        const overtimeBtn = document.getElementById("overtime");
        let lastPhotoData = null;
        let locationData = "Fetching location...";
        let selectedLocation = "In"; // Default location

        navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
            .then(stream => { video.srcObject = stream; })
            .catch(err => console.error("Camera access denied:", err));

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const { latitude, longitude } = position.coords;
                    try {
                        const response = await fetch(
                            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`
                        );
                        const data = await response.json();
                        locationData = data.display_name || "Unknown Location";
                    } catch (error) {
                        console.error("Error fetching location name:", error);
                        locationData = "Location fetch failed";
                    }
                },
                (error) => {
                    console.error("Error fetching location:", error);
                    locationData = "Location access denied";
                }
            );
        } else {
            locationData = "Geolocation not supported by this browser.";
        }

        const captureImage = (location) => {
            selectedLocation = location;  // Set the location based on the button clicked
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            ctx.fillStyle = "white";
            ctx.font = "15px Arial";
            const dateTime = new Date().toLocaleString();
            ctx.fillText(dateTime, 20, canvas.height - 90);
            ctx.fillText(locationData, 20, canvas.height - 50); // Display location name

            lastPhotoData = canvas.toDataURL("image/jpeg");
            photo.src = lastPhotoData;
            photo.style.display = "block";
            video.style.display = "none";
            captureBtn.style.display = "none";
            retakeBtn.style.display = "block";
            confirmBtn.style.display = "block";
        };

        captureBtn.addEventListener("click", () => captureImage("In"));
        undertimeBtn.addEventListener("click", () => captureImage("Undertime"));
        overtimeBtn.addEventListener("click", () => captureImage("Overtime"));

        retakeBtn.addEventListener("click", () => {
            photo.style.display = "none";
            video.style.display = "block";
            captureBtn.style.display = "block";
            retakeBtn.style.display = "none";
            confirmBtn.style.display = "none";
        });

        confirmBtn.addEventListener("click", () => {
            console.log("Sending image:", lastPhotoData); // Debugging: check if image data is present
            fetch("upload_image.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    image: lastPhotoData, // Base64 image data
                    location: selectedLocation, // Send the selected location
                }),
            })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert("Image uploaded successfully!");
                    retakeBtn.click(); // Reset the camera view
                } else {
                    alert("Image upload failed: " + data.error);
                }
            })
            .catch((error) => {
                console.error("Error uploading image:", error);
                alert("An error occurred while uploading the image.");
            });
        });
    </script>
</body>
</html>
