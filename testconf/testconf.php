<?php
require 'vendor/autoload.php'; // Include Composer's autoloader if you're using the SDK via Composer

use Agora\RtcTokenBuilder;

// Replace with your actual Agora App ID and App Certificate
$appID = "ad9d0e39ec7d499b8cd54b5aaea0ec6c"; // Replace with your Agora App ID
$appCertificate = "78f98177decc40849391308b48659c83"; // Replace with your Agora App Certificate
$channelName = "test_channel"; // Static channel name for testing
$uid = 0; // Set to 0 for an auto-generated user ID
$expireTimeInSeconds = 3600; // Token expiration time in seconds
$currentTimestamp = time();
$expireTimestamp = $currentTimestamp + $expireTimeInSeconds;

// Generate the token
$token = RtcTokenBuilder::buildTokenWithUserAccount($appID, $appCertificate, $channelName, $uid, RtcTokenBuilder::RolePublisher, $expireTimestamp);

// Return the token as JSON
echo json_encode(['token' => $token, 'channel' => $channelName]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agora Video Call</title>
    <script src="https://cdn.jsdelivr.net/npm/agora-rtc-sdk-ng@4.0.0/dist/agora-rtc-sdk-ng.umd.min.js"></script>
    <style>
        #video-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            width: 100%;
            height: 100vh;
        }
        video {
            width: 400px;
            height: 300px;
            border: 1px solid #000;
            margin: 10px;
        }
    </style>
</head>
<body>

<div id="video-container"></div>
<button id="join-btn">Join Call</button>

<script>
    const client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
    const channelName = "test_channel"; // Static channel name for testing
    let localTracks = [];

    async function joinCall() {
        // Fetch the token from your PHP backend
        const response = await fetch('generate_token.php');
        const data = await response.json();
        const token = data.token;

        // Initialize the client
        await client.join(token, channelName, null, null);
        
        // Create a local audio and video track
        localTracks = await AgoraRTC.createMicrophoneAndCameraTracks();

        // Publish the local tracks
        await client.publish(localTracks);

        // Display the local video stream
        const localVideoContainer = document.createElement('div');
        const localVideo = document.createElement('video');
        localVideo.autoplay = true;
        localVideo.srcObject = localTracks[1].mediaStream;
        localVideoContainer.appendChild(localVideo);
        document.getElementById('video-container').appendChild(localVideoContainer);

        // Subscribe to remote users' streams
        client.on("user-published", async (user, mediaType) => {
            await client.subscribe(user, mediaType);
            if (mediaType === 'video') {
                const remoteVideo = document.createElement('video');
                remoteVideo.autoplay = true;
                remoteVideo.srcObject = user.videoTrack.mediaStream;
                document.getElementById('video-container').appendChild(remoteVideo);
            }
        });
    }

    document.getElementById('join-btn').onclick = joinCall;
</script>

</body>
</html>
