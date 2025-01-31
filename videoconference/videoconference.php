<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

<!-- sweetalert -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require '../config.php';
require 'agora/RtcTokenBuilder.php';
header("Access-Control-Allow-Origin: https://konsultap.com");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}



// Check if the user is logged in and has one of the allowed roles
if (!isset($_SESSION['IdNumber']) || empty($_SESSION['IdNumber']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['medprac', 'faculty', 'student'])) {
    // echo "Invalid role or not logged in";
    // exit;
    
    echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script> 
    $(this).ready(function () {
        Swal.fire({
            icon: "warning",
            title: "Please Login",
            text: "You are not authorized to join this video conference.",
            showCloseButton: true,
            showConfirmButton: false
        })
    })      

    setTimeout(function name(params) {
        document.location = "../index.php";
    }, 3000);
    </script>';
}

// Check if the user has completed OTP verification
elseif (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header("Location: ../verification.php");
    exit;
}

if (!isset($_GET['videoconference_id'])) {
    die('Invalid Request');
}

$videoconference_id = $_GET['videoconference_id'];
if (isset($_SESSION['IdNumber'])) {
    $userId = $_SESSION['IdNumber'];

    
    // Fetch the video conference details from the database
    $query = "SELECT idNumber_user1, idNumber_user2, active_participants
              FROM videoconference
              WHERE videoconference_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $videoconference_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $user1Id = $row['idNumber_user1'];
        $user2Id = $row['idNumber_user2'];
        $activeParticipants = $row['active_participants'];
    
        // Check if the current user is authorized to join
        if ($_SESSION['IdNumber'] === $user1Id || $_SESSION['IdNumber'] === $user2Id) {
            // Check active participants count
            if ($activeParticipants >= 2) {
                // Block the third participant and redirect to an error page
                header("Location: error.php");
                exit;
            } else {
                // Increment active participants count
                $activeParticipants++;
                $updateQuery = "UPDATE videoconference SET active_participants = ? WHERE videoconference_id = ?";
                $updateStmt = mysqli_prepare($conn, $updateQuery);
                mysqli_stmt_bind_param($updateStmt, "is", $activeParticipants, $videoconference_id);
                mysqli_stmt_execute($updateStmt);
            }
        } else {        
            if (isset($_SESSION['role'])) {
                if ($_SESSION['role'] === 'medprac') {
                    $location = '../med/dashboard.php'; // Redirect to medprac dashboard
                } else if ($_SESSION['role'] === 'student' || $_SESSION['role'] === 'faculty') {
                    $location = '../main/dashboard.php'; // Redirect to main dashboard for students and faculty
                } else {
                    $location = '../main/dashboard.php'; // Fallback redirection (could also be an error page)
                }
            
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
                <script> 
                $(this).ready(function () {
                    Swal.fire({
                        icon: "warning",
                        title: "Error",
                        text: "You are not authorized to join this video conference.",
                        showCloseButton: true,
                        showConfirmButton: false
                    })
                })  
        
                setTimeout(function name(params) {
                    document.location = "'.$location.'";
                }, 4000);
                </script>';
            }
        }
    } else {
        die('Video conference not found');
    }


    // Agora credentials
    $appID = 'ad9d0e39ec7d499b8cd54b5aaea0ec6c';
    $appCertificate = '78f98177decc40849391308b48659c83';
    $channelName = $videoconference_id;
    
    // Generate Agora token
    // $token = generateAgoraToken($appID, $appCertificate, $channelName, $_SESSION['IdNumber']); // Use session IdNumber for token
    $token = generateAgoraToken($appID, $appCertificate, $channelName, $userId); // Use session IdNumber for token
}
    function generateAgoraToken($appID, $appCertificate, $channelName, $uid) {
        $expireTimeInSeconds = 3600;
        $currentTimeStamp = (new DateTime())->getTimestamp();
        $privilegeExpiredTs = $currentTimeStamp + $expireTimeInSeconds;
        return RtcTokenBuilder::buildTokenWithUid($appID, $appCertificate, $channelName, $uid, RtcTokenBuilder::RolePublisher, $privilegeExpiredTs);
    }


?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KonsulTap</title>
    <link rel="icon" href="../images/logo_icon.png" type="image/icon type">
    
    <!-- timeout -->
    <script src="../timeout.js"></script> 

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        #videos {
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }
        
        .video-player {
            background-image: url('../images/bg-wallpaper.png');
            width: 100%;
            height: 100%; /* Use auto to maintain aspect ratio */
            flex-grow: 1; /* Allow the video to grow and fill the space */
        }
        
        #user-2 {
            display: none;
        }
        
        .smallFrame {
            position: fixed;
            top: 20px;
            left: 20px;
            height: 170px;
            width: 300px;
            border-radius: 5px;
            border: 2px solid #FFDE59;
            box-shadow: 3px 3px 15px -1px rgba(0,0,0,0.77);
            z-index: 999;
        }
        
        .controls {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 1em;
        }
        
        .controls button {
            padding: 15px;
            background-color: #000066;
            border: none;
            border-radius: 100%;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        
        /*#time-left {*/
            position: fixed;   /* Fix the element to the screen */
            top: 10px;         /* Distance from the top of the screen */
            right: 10px;        /* Distance from the left side of the screen */
            font-size: 18px;   /* Set the font size */
            background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
            color: white;      /* White text color */
            padding: 5px 10px; /* Padding around the text */
            border-radius: 5px; /* Rounded corners */
            z-index: 9999;     /* Make sure it's on top of other elements */
            font-family: Arial, sans-serif; /* Optional: set a readable font */
        /*}*/
        
        .bi {
            font-size: 20px;
        }
        
        #disconnect-btn {
            background-color: #F23F43;
        }
        
        /*Phone landscape*/
        @media screen and (max-height: 500px) {
            .smallFrame {
                height: 90px;
                width: 150px;
            }
        }
        
        /*Phone portrait*/
        @media screen and (max-width: 600px) {
            .smallFrame {
                width: 100px;
            }
            
            .video-player {
            height: auto; /* Allow height to adjust for smaller screens */
            }
        }
        
       
    </style>
</head>
<body>

    <div id="videos" class="video-container">
        <!-- Video elements for local and remote streams -->
        <video class="video-player" id="user-1" autoplay playsinline ></video>
        <video class="video-player" id="user-2" autoplay playsinline ></video>
    </div>

    <!--<p id="time-left">Time Left: 1:00:00</p>-->

    <!-- Control buttons for camera and mic -->
    <div class="controls">
        <button id="camera-btn"><i class="bi bi-camera-video-fill"></i></button>
        <button id="mic-btn"><i class="bi bi-mic-fill"></i></button>
        <button id="disconnect-btn"><i class="bi bi-telephone-forward"></i></i></button> <!-- New button -->
    </div>

    <!-- Agora and the custom script -->
    <script src="https://cdn.jsdelivr.net/npm/agora-rtm-sdk@2.2.0/agora-rtm.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/agora-rtc-sdk@latest/dist/AgoraRTCSDK.min.js"></script>-->
    <script src="https://cdn.jsdelivr.net/npm/agora-rtc-sdk-ng@4.22.1/AgoraRTC_N-production.min.js"></script>

    <script src="agora-rtm-sdk-1.5.1.js"></script>

<script>
    // // Function to initialize a 1-hour timer with alerts
    // function startCallTimer() {
    //     const maxDuration = 3600; // max duration in seconds (1 hour)
    //     const warningTime = 300;  // 5 minutes before end (in seconds)
    //     let timeLeft = maxDuration;
        
    //     // Function to format time in MM:SS
    //     function formatTime(seconds) {
    //         const minutes = Math.floor(seconds / 60);
    //         const remainingSeconds = seconds % 60;
    //         return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
    //     }

    //     const timerInterval = setInterval(() => {
    //         timeLeft--;

    //         // Show warning when timeLeft is 5 minutes
    //         if (timeLeft === warningTime) {
    //             Swal.fire({
    //                 icon: 'warning',
    //                 title: 'Session Ending Soon',
    //                 text: 'Your session will end in 5 minutes. Please wrap up.',
    //                 showCloseButton: true,
    //             });
    //         }
            
    //         // Update the UI with the formatted time left
    //         document.getElementById('time-left').textContent = `Time Left: ${formatTime(timeLeft)}`;

    //         // End call when timeLeft reaches 0
    //         if (timeLeft <= 0) {
    //             clearInterval(timerInterval);
    //             endCall(); // Function to handle the end of the call
    //         }
    //     }, 1000); // update every second
    // }
    
    let callStartTime = null;  // To store the time when the call starts
    let timerInterval;
    let totalCallDuration = 0;  // To store the total duration of the call in seconds
    
    // Start the timer only when both users join
    let usersJoined = 0;
    
    // Function to initialize the timer
    function startCallTimer() {
        const maxDuration = 3600; // max duration in seconds (1 hour)
        const warningTime = 300;  // 5 minutes before end (in seconds)
        let timeLeft = maxDuration;
        
        // Function to format time in MM:SS
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
        }
    
        // Update the timer UI every second
        timerInterval = setInterval(() => {
            timeLeft--;
            totalCallDuration++;  // Increment the total call duration
    
            // Show warning when timeLeft is 5 minutes
            if (timeLeft === warningTime) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Session Ending Soon',
                    text: 'Your session will end in 5 minutes. Please wrap up.',
                    showCloseButton: true,
                });
            }
    
            // Update the UI with the formatted time left
            // document.getElementById('time-left').textContent = `Time Left: ${formatTime(timeLeft)}`;
    
            // End the call when timeLeft reaches 0
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                endCall();  // Function to handle the end of the call
            }
        }, 1000); // update every second
    }

    // Function to end the call and handle redirection based on role
    function endCall() {
        Swal.fire({
            icon: 'info',
            title: 'Session Ended',
            text: 'The call will now disconnect.',
            showConfirmButton: true,
        }).then(() => {
            leaveAndRedirectBasedOnRole(); // Leave the channel and redirect based on role
        });
        
        const durationInSeconds = Math.floor((Date.now() - callStartTime) / 1000); // Call duration in seconds
        sendCallDurationToServer(durationInSeconds);
    }
    
    // Function to send call duration to the server
    async function sendCallDurationToServer(durationInSeconds) {
        try {
            const response = await fetch('save_call_duration', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    videoconference_id: roomId, // Get the room ID from the URL or state
                    duration: durationInSeconds, // Duration in seconds
                }),
            });
    
            if (!response.ok) {
                console.error('Failed to save call duration');
            } else {
                console.log('Call duration saved successfully');
            }
        } catch (error) {
            console.error('Error saving call duration:', error);
        }
    }

    // Function to leave the channel and redirect based on user role
    async function leaveAndRedirectBasedOnRole() {
        await leaveChannel(); // Call leaveChannel function (Agora disconnect logic)

        // Determine where to redirect based on the user's role stored in the session
        let userRole = '<?php echo $_SESSION['role']; ?>'; // Get the role from the PHP session

        if (userRole === 'medprac') {
            window.location.href = '../med/dashboard.php'; // Redirect to medprac dashboard
        } else if (userRole === 'student' || userRole === 'faculty') {
            window.location.href = '../main/dashboard.php'; // Redirect to main dashboard for students and faculty
        } else {
            window.location.href = '../main/dashboard.php'; // Fallback redirection (could also be an error page)
        }
    }

    // Start the timer when the page loads or the call starts
    $(document).ready(function() {
        startCallTimer();
    });
</script>


    <script>

        
        let APP_ID = "ad9d0e39ec7d499b8cd54b5aaea0ec6c";
        let token = null;
        let uid = String(Math.floor(Math.random() * 10000));

        let client;
        let channel;
        let queryString = window.location.search;
        let urlParams = new URLSearchParams(queryString);
        let roomId = urlParams.get('videoconference_id'); // Ensure the correct query parameter name

        if (!roomId) {
            window.location = lobby.php;
        }

        let localStream;
        let remoteStream;
        let peerConnection;

        const servers = {
             iceServers: [
                // WOKRING 10-11-10-25PM
                { urls: 'stun:stun.cloudflare.com:3478' }, // Cloudflare STUN server
                {
                    urls: 'turn:turn.speed.cloudflare.com:50000',
                    username: 'd2e123c2b6dde6ce31e5b0322e72cd1bf5261bb88fed8de05e5f361d53bc3e82798a2bb587d55507c696834d33cd44748d0bd875ea60874d22baffd018b465d2',
                    credential: 'aba9b169546eb6dcc7bfb1cdf34544cf95b5161d602e3b5fa7c8342b2e9802fb'
                },
                
                {
                    urls: "stun:stun.relay.metered.ca:80",
                },
                {
                    urls: "turn:global.relay.metered.ca:80",
                    username: "3bc06962451566c3f15f1ef6",
                    credential: "arePjagDVIKGD+uE",
                },
                {
                    urls: "turn:global.relay.metered.ca:80?transport=tcp",
                    username: "3bc06962451566c3f15f1ef6",
                    credential: "arePjagDVIKGD+uE",
                },
                {
                    urls: "turn:global.relay.metered.ca:443",
                    username: "3bc06962451566c3f15f1ef6",
                    credential: "arePjagDVIKGD+uE",
                },
                {
                    urls: "turns:global.relay.metered.ca:443?transport=tcp",
                    username: "3bc06962451566c3f15f1ef6",
                    credential: "arePjagDVIKGD+uE",
                },
                
                
                
            //  { urls: 'stun:stun.ekiga.net:3478' },
            // { urls: 'stun:stun1.l.google.com:19302' },
            // { urls: 'stun:stun2.l.google.com:19302' },
            // {
            // urls: 'turn:turn.services.mozilla.com',
            // username: 'webrtc',
            // credential: 'webrtc'
            // },
            // {
            //     urls: 'turn:relay.backups.cz',
            //     username: 'user',
            //     credential: 'pass'
            // },
            // {
            //     urls: 'turn:anyfirewall.com:443',
            //     username: 'webrtc',
            //     credential: 'webrtc'
            // },
            
            // { urls: 'stun:stun.services.mozilla.com' },
            // { urls: 'stun:turn.anyfirewall.com:443' }
            // { urls: 'stun:stun.services.mozilla.com' },
            // {
            //     urls: 'turn:turn.service.mozilla.com',
            //     username: 'webrtc',
            //     credential: 'webrtc'
            // }
            
              // Mozilla STUN server
            // { urls: 'stun:stun1.iptel.org' },           // Iptel STUN server
            // { urls: 'stun:stun.lc.telefonica.net' }     // Telefonica STUN server
        ]
        };

    let constraints = {
        video: { width: { min: 640, ideal: 1920, max: 1920 }, height: { min: 480, ideal: 1080, max: 1080 } },
        audio: true
    };

let init = async () => {
    client = AgoraRTM.createInstance(APP_ID);
    await client.login({ uid, token });

    channel = client.createChannel(roomId);
    await channel.join();

    channel.on('MemberJoined', handleUserJoined);
    channel.on('MemberLeft', handleUserLeft);
    client.on('MessageFromPeer', handleMessageFromPeer);
    
    usersJoined = 1;
    console.log('Users joined: ', usersJoined);

    await requestPermissions();
};

async function requestPermissions() {
    try {
        // Check if permission has already been granted for camera and microphone
        let cameraPermission = await navigator.permissions.query({ name: 'camera' });
        let microphonePermission = await navigator.permissions.query({ name: 'microphone' });

        if (cameraPermission.state === 'denied' || microphonePermission.state === 'denied') {
            // Permission denied previously, inform the user or guide them to reset permissions
            alert('Camera or microphone permission denied. Please reset permissions in your browser settings.');
        } else {
            // If not denied, try requesting access again
            localStream = await navigator.mediaDevices.getUserMedia(constraints);
            document.getElementById('user-1').srcObject = localStream;
            console.log('Local stream started');
        }

    }
    
    catch (error) {
        console.error('Error accessing media devices.', error);
    }
}


        let handleUserLeft = async (MemberId) => {
            usersJoined--;
            
            // Broadcast the updated usersJoined count to all clients
            await channel.sendMessage({
                text: JSON.stringify({
                    type: 'user_count',
                    usersJoined: usersJoined,
                })
            });
            
            console.log('Users joined: ', usersJoined);
            
            document.getElementById('user-2').style.display = 'none';
            document.getElementById('user-1').classList.remove('smallFrame');
            
            // Stop the timer if only one user is left
            if (usersJoined === 1) {
                clearInterval(timerInterval);
                totalCallDuration = Math.floor((Date.now() - callStartTime) / 1000);  // Calculate the duration
                endCall();
            }
        };

        let handleMessageFromPeer = async (message, MemberId) => {
            message = JSON.parse(message.text);
            
            if (message.type === 'user_count') {
                // Once we receive the user count message, update the usersJoined count
                usersJoined = message.usersJoined;
                console.log('Received updated usersJoined count: ', usersJoined);
        
                // Start the timer only when both users are in the call
                if (usersJoined === 2 && callStartTime === null) {
                    callStartTime = Date.now();
                    startCallTimer();  // Start the call timer
                }
            }

            if (message.type === 'offer') {
                createAnswer(MemberId, message.offer);
            }

            if (message.type === 'answer') {
                addAnswer(message.answer);
            }

            if (message.type === 'candidate') {
                if (peerConnection) {
                    peerConnection.addIceCandidate(message.candidate);
                }
            }
        };

        let handleUserJoined = async (MemberId) => {
            console.log("User joined: ", MemberId);
            try {
                // Broadcast the updated usersJoined count to all clients
                await channel.sendMessage({
                    text: JSON.stringify({
                        type: 'user_count', // Type of message
                        usersJoined: usersJoined + 1  // Increment usersJoined by 1
                    })
                });
        
                // Increment usersJoined locally
                usersJoined++;
                console.log('Updated usersJoined (local): ', usersJoined);
        
                // Start the timer only when both users are in the call
                if (usersJoined === 2 && callStartTime === null) {
                    callStartTime = Date.now();
                    startCallTimer();  // Start the call timer
                    console.log('Starting the call timer...');
                }
        
                // Create an offer for the new user who joined the channel
                console.log('A new user joined the channel, creating offer: ', MemberId);
                createOffer(MemberId);
            } catch (error) {
                console.error('Error sending user count message: ', error);
            }
        };

        let createPeerConnection = async (MemberId) => {
            peerConnection = new RTCPeerConnection(servers);

            remoteStream = new MediaStream();
            document.getElementById('user-2').srcObject = remoteStream;
            document.getElementById('user-2').style.display = 'block';

            document.getElementById('user-1').classList.add('smallFrame');

            if (!localStream) {
                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                document.getElementById('user-1').srcObject = localStream;
            }

            localStream.getTracks().forEach((track) => {
                peerConnection.addTrack(track, localStream);
            });

            peerConnection.ontrack = (event) => {
                event.streams[0].getTracks().forEach((track) => {
                    remoteStream.addTrack(track);
                });
            };

          peerConnection.onicecandidate = async (event) => {
    if (event.candidate) {
        console.log('New ICE candidate:', event.candidate);
        client.sendMessageToPeer({ text: JSON.stringify({ 'type': 'candidate', 'candidate': event.candidate }) }, MemberId);
    } else {
        console.log('All ICE candidates have been sent');
    }
};

        };

        let createOffer = async (MemberId) => {
            await createPeerConnection(MemberId);
            let offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);
            client.sendMessageToPeer({ text: JSON.stringify({ 'type': 'offer', 'offer': offer }) }, MemberId);
        };

        let createAnswer = async (MemberId, offer) => {
            await createPeerConnection(MemberId);
            await peerConnection.setRemoteDescription(offer);
            let answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);
            client.sendMessageToPeer({ text: JSON.stringify({ 'type': 'answer', 'answer': answer }) }, MemberId);
        };

        let addAnswer = async (answer) => {
            if (!peerConnection.currentRemoteDescription) {
                peerConnection.setRemoteDescription(answer);
            }
        };

        let leaveChannel = async () => {
            await channel.leave();
            await client.logout();

            // Call to update the active participants count
            let response = await fetch('update_participants', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    'videoconference_id': roomId,
                    'action': 'decrement'
                })
            });

            if (!response.ok) {
                console.error('Failed to update participants count');
            }
        };

        let leaveChannelAndRedirect = async () => {
    await leaveChannel();

    // Determine where to redirect based on the user's role stored in the session
    let userRole = '<?php echo $_SESSION['role']; ?>'; // Get the role from the PHP session

    if (userRole === 'medprac') {
        window.location.href = '../med/dashboard.php'; // Redirect to medprac dashboard
    } else if (userRole === 'student' || userRole === 'faculty') {
        window.location.href = '../main/dashboard.php'; // Redirect to main dashboard for students and faculty
    } else {
        window.location.href = '../main/dashboard.php'; // Fallback redirection (could also be an error page)
    }
};
        let toggleCamera = async () => {
            if (localStream) {
                let videoTrack = localStream.getTracks().find(track => track.kind === 'video');

                if (videoTrack) {
                    videoTrack.enabled = !videoTrack.enabled;
                    console.log('Video track enabled:', videoTrack.enabled);
                    document.getElementById('camera-btn').style.backgroundColor = videoTrack.enabled ? '#000066' : '#76a6e5';
                    document.getElementById('camera-btn').querySelector('i').className = videoTrack.enabled ? 'bi bi-camera-video-fill' : 'bi bi-camera-video-off-fill';
                } else {
                    console.warn('No video track found.');
                }
            } else {
                console.warn('Local stream not found.');
            }
        };

        let toggleMic = async () => {
            if (localStream) {
                let audioTrack = localStream.getTracks().find(track => track.kind === 'audio');

                if (audioTrack) {
                    audioTrack.enabled = !audioTrack.enabled;
                    console.log('Audio track enabled:', audioTrack.enabled);
                    document.getElementById('mic-btn').style.backgroundColor = audioTrack.enabled ? '#000066' : '#76a6e5';
                    document.getElementById('mic-btn').querySelector('i').className = audioTrack.enabled ? 'bi bi-mic-fill' : 'bi bi-mic-mute-fill';
                } else {
                    console.warn('No audio track found.');
                }
            } else {
                console.warn('Local stream not found.');
            }
        };

  window.addEventListener('online', async () => {
    console.log('Back online');
    await requestPermissions(); // Reinitialize the stream
});

window.addEventListener('offline', () => {
    console.log('Went offline');
    // Optional: Clean up the stream if needed
});

  
  
    window.addEventListener('beforeunload', function() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_participants', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('videoconference_id=<?php echo htmlspecialchars($videoconference_id); ?>&action=decrement');
    });

        document.getElementById('camera-btn').addEventListener('click', toggleCamera);
        document.getElementById('mic-btn').addEventListener('click', toggleMic);
        document.getElementById('disconnect-btn').addEventListener('click', leaveChannelAndRedirect); // New button event listener

        init();

        
    </script>
</body>
</html>