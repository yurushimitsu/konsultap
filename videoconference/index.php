<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Video Conference</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel = 'stylesheet' type='text/css' media = 'screen' href="main.css">
</head>
<body>

  <!-- Container for video  -->
    <div id = "videos">
        <video class="video-player" id="user-1" autoplay playsinline> </video>
        <video class="video-player" id="user-2" autoplay playsinline> </video>

    </div>

    <div id = "controls">

        <div class = "control-container" id="camera-btn">
            <img src = "icons/camera.png" alt = "camera">
        </div>

        <div class = "control-container" id="mic-btn">
            <img src = "icons/mic.png" alt = "camera">
        </div>

    <a href="lobby.php"> 
        <div class = "control-container" id="leave-btn">
            <img src = "icons/phone.png" alt = "camera">
        </div>
    </a>

    </div>
    
</body>
<script src='agora-rtm-sdk-1.5.1.js'></script>
<script src="main.js"></script>
</html>