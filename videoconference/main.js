// Using agora sdk app id 
// We will use token authentication if we are going to production 
let APP_ID = "ad9d0e39ec7d499b8cd54b5aaea0ec6c";

// Ito yung gagamitin pag production na tayo. For now null muna siya kasi app_id ang gagamitin 
let token = null;

// Dito maiidentify ang mga users pwede tayo gumamit ng id sa database or random uid muna
// Kada may bagong users sa page mag gegenerate siya random uid 
let uid = String (Math.floor(Math.random() * 10000))

// Client Variable is used to store an instance of the Agora Real-Time Messaging (RTM) client.
let client;
// Dito yung channel mismo 
let channel;

let queryString = window.location.search
let urlParams = new URLSearchParams(queryString)
let roomId = urlParams.get('room')

// Redirects user sa ibang page pag tinry mag break in sa meeting room 
if(!roomId){
    window.location = 'lobby.html'
}

// My User Stream interface (audio, video)
let localStream;

// Another user's Stream interface (audio, video)
let remoteStream;

let peerConnection;

const servers = {
    iceServers: [
        {
            urls: ['stun:stun1.l.google.com:19302', 'stun:stun2.l.google.com:19302']
        }
    ]
}

let constraints = {
    video:{
        width:{min:640, ideal:1920, max:1920},
        height:{min:480, ideal:1080, max:1080},
    },
    audio:true
}

let init = async () => {

    client = await AgoraRTM.createInstance(APP_ID)
    await client.login({uid, token})

    //index.html?room=234234
    channel = client.createChannel(roomId)
    await channel.join()


    // Bale gagana to pag may user na naka access sa website at mag ttrigger ang function na handleUserJoined
    channel.on('MemberJoined', handleUserJoined)
    channel.on('MemberLeft', handleUserLeft)

    client.on('MessageFromPeer', handleMessageFromPeer)

   
    // Request for permission to enter audio and video 
    localStream = await navigator.mediaDevices.getUserMedia(constraints);

    document.getElementById('user-1').srcObject = localStream
}

let handleUserLeft = (MemberId) => {
    document.getElementById('user-2').style.display = 'none'
    document.getElementById('user-1').classList.remove('smallFrame')
}


let handleMessageFromPeer = async (message, MemberId) => {
    message = JSON.parse(message.text);

    if(message.type === 'offer') {
        createAnswer(MemberId, message.offer)
    }

    // Sending the message back 
    if(message.type === 'answer') {
        addAnswer(message.answer)
    }

    //How we add candidate to peer connection 
    if(message.type === 'candidate') {
        if(peerConnection){
            peerConnection.addIceCandidate(message.candidate)
        }
    }
}


let handleUserJoined = async (MemberId) => {
    console.log('A new user joined the channel: ', MemberId)
    createOffer(MemberId)
}



let createPeerConnection = async (MemberId) => {
    peerConnection = new RTCPeerConnection(servers);

    remoteStream = new MediaStream()
    document.getElementById('user-2').srcObject = remoteStream
    document.getElementById('user-2').style.display = 'block'

    document.getElementById('user-1').classList.add('smallFrame')

    if(!localStream){
        localStream = await navigator.mediaDevices.getUserMedia({video: true , audio: true});
        document.getElementById('user-1').srcObject = localStream
    }

    //Add audio and Video tracks to peer stream
    localStream.getTracks().forEach((track) =>{
        peerConnection.addTrack(track, localStream)
    })


    //When peer adds their tracks also add to local stream
    peerConnection.ontrack = (event) => {
        event.streams[0].getTracks().forEach((track) => {
            remoteStream.addTrack(track)
        })
    }

    //Generate and console out ice candidates 
    peerConnection.onicecandidate = async (event) => {
        if(event.candidate){
            client.sendMessageToPeer({text:JSON.stringify({'type': 'candidate', 'candidate':event.candidate})}, MemberId)

        }
    }
}


// Pag may nagjoin na isang user
let createOffer = async (MemberId) => {

    await createPeerConnection(MemberId)

    // Every peer connection will have offer and answer setlocalDescription will trigger ice candidate
    // Peer 1 sets localDescription and sends out the offer 
    let offer = await peerConnection.createOffer()
    await peerConnection.setLocalDescription(offer)

    //Sending the offer once its created 

    client.sendMessageToPeer({text:JSON.stringify({'type': 'offer', 'offer':offer})}, MemberId)
}



// 
let createAnswer = async (MemberId, offer) => {
    await createPeerConnection(MemberId)

    // Remote description is offer and local description is answer 
    // Peer 2 when they get the offer sets the remote description and local description 
    await peerConnection.setRemoteDescription(offer)

    let answer = await peerConnection.createAnswer()
    await peerConnection.setLocalDescription(answer)

    client.sendMessageToPeer({text:JSON.stringify({'type': 'answer', 'answer':answer})}, MemberId)
}

let addAnswer = async (answer) => {

    if(!peerConnection.currentRemoteDescription) {
        peerConnection.setRemoteDescription(answer)
    }
}


let leaveChannel = async () => {
    await channel.leave()
    await client.logout()
}

let toggleCamera = async () => {
    let videoTrack = localStream.getTracks().find(track => track.kind === 'video')

    if(videoTrack.enabled){
        videoTrack.enabled = false
        document.getElementById('camera-btn').style.backgroundColor = 'rgb(255, 80, 80)'
    } 
    else{
        videoTrack.enabled = true
        document.getElementById('camera-btn').style.backgroundColor = 'rgb(179, 102, 249, .9)'
    }
}

let toggleMic = async () => {
    let audioTrack = localStream.getTracks().find(track => track.kind === 'audio')

    if(audioTrack.enabled){
        audioTrack.enabled = false
        document.getElementById('mic-btn').style.backgroundColor = 'rgb(255, 80, 80)'
    } 
    else{
        audioTrack.enabled = true
        document.getElementById('mic-btn').style.backgroundColor = 'rgb(179, 102, 249, .9)'
    }
}

// Automatic magleleave user pag nag x sa browser 
window.addEventListener('beforeunload' , leaveChannel)

document.getElementById('camera-btn').addEventListener('click', toggleCamera)
document.getElementById('mic-btn').addEventListener('click', toggleMic)

init()

