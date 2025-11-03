<?php
require_once 'includes/db.php';
require_once 'includes/session.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Verification - NEUST Repository System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <style>
    body {
        background-color: #f4f6fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
    }

    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    .verification-card {
        background-color: #fff;
        width: 800px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        animation: fadeIn 0.4s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .card-header {
        background-color: #083b73;
        color: white;
        text-align: center;
        padding: 18px;
        font-size: 20px;
        font-weight: bold;
    }

    .card-body {
        padding: 25px;
    }

    .card-body p {
        font-size: 14px;
        color: #555;
        margin-bottom: 20px;
        text-align: center;
    }

    label {
        font-weight: 600;
        font-size: 14px;
        color: #333;
        display: block;
        margin-bottom: 6px;
    }

    input[type="text"], select, input[type="file"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-bottom: 15px;
        font-size: 14px;
        transition: 0.3s;
    }

    input[type="text"]:focus, select:focus, input[type="file"]:focus {
        border-color: #083b73;
        outline: none;
    }

    .note {
        font-size: 12px;
        color: #777;
        text-align: center;
        margin-bottom: 20px;
    }

    .btn {
        display: block;
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-primary {
        background-color: #083b73;
        color: #fff;
    }

    .btn-primary:hover {
        background-color: #0d4b94;
    }

    .btn-secondary {
        background-color: #9da4ae;
        color: #fff;
        margin-top: 10px;
    }

    .btn-secondary:hover {
        background-color: #7d8590;
    }
</style>
</head>
<body>

<div class="container">
    <div class="verification-card">
        <div class="card-header">Student Verification</div>
        <div class="card-body">
            <p>Please provide your student information to verify your identity. This is required to request full manuscripts.</p>
            <form action="submit_verification.php" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <label for="student_id">Student ID Number</label>
                        <input type="text" id="student_id" name="student_id" placeholder="Enter Student ID" required>

                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" placeholder="Enter Full Name" required>

                        <label for="section">Section</label>
                        <input type="text" id="section" name="section" placeholder="Enter Section" required>

                        <label for="year_level">Year Level</label>
                        <select id="year_level" name="year_level" required>
                            <option value="">Select Year Level</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>

                        <label for="school_id">Upload School ID (JPG, PNG, or PDF)</label>
                        <input type="file" id="school_id" name="school_id" accept=".jpg,.jpeg,.png,.pdf" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="face_id">Face ID Verification</label>
                        <div id="camera-container" style="width: 100%; height: 280px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 15px; position: relative;">
                            <video id="camera" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;" autoplay></video>
                            <canvas id="canvas" style="display: none;"></canvas>
                            <button type="button" id="capture-btn" class="btn btn-primary" style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); width: auto; padding: 8px 15px;">
                                <i class="fas fa-camera"></i> Capture
                            </button>
                        </div>
                        <input type="hidden" id="face_id" name="face_id">
                    </div>
                </div>
                
                <p class="note">Please upload a clear image of your school ID and capture your face for verification.</p>

                <div class="row">
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary">Submit for Verification</button>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Face ID capture functionality
    document.addEventListener('DOMContentLoaded', function() {
        const video = document.getElementById('camera');
        const canvas = document.getElementById('canvas');
        const captureBtn = document.getElementById('capture-btn');
        const faceIdInput = document.getElementById('face_id');
        const cameraContainer = document.getElementById('camera-container');
        
        // Access the user's camera
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(stream) {
                    video.srcObject = stream;
                })
                .catch(function(error) {
                    console.error("Camera error: ", error);
                    cameraContainer.innerHTML = '<div style="text-align: center; padding: 20px; color: #721c24; background-color: #f8d7da; border-radius: 6px;">Camera access denied or not available. Please allow camera access to use Face ID verification.</div>';
                });
        } else {
            cameraContainer.innerHTML = '<div style="text-align: center; padding: 20px; color: #721c24; background-color: #f8d7da; border-radius: 6px;">Your browser does not support camera access.</div>';
        }
        
        // Capture button click event
        captureBtn.addEventListener('click', function() {
            // Set canvas dimensions to match video
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Draw the current video frame on the canvas
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Convert canvas to base64 image data
            const imageData = canvas.toDataURL('image/png');
            
            // Store the image data in the hidden input
            faceIdInput.value = imageData;
            
            // Show a preview of the captured image
            video.style.display = 'none';
            canvas.style.display = 'block';
            captureBtn.innerHTML = '<i class="fas fa-redo"></i> Retake';
            captureBtn.addEventListener('click', function() {
                // Reset to camera view for retake
                video.style.display = 'block';
                canvas.style.display = 'none';
                captureBtn.innerHTML = '<i class="fas fa-camera"></i> Capture';
            }, { once: true });
        });
    });
</script>
</body>
</html>