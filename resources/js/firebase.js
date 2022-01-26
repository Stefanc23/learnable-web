import firebase from 'firebase';

const firebaseConfig = {
    apiKey: "AIzaSyC7KIwmwodHtX2Ps5M2mTeHHtjH-jaeWaM",
    authDomain: "learnable-app.firebaseapp.com",
    projectId: "learnable-app",
    storageBucket: "learnable-app.appspot.com",
    messagingSenderId: "816702698850",
    appId: "1:816702698850:web:ac8b87dcd2063d4615e83b",
};

firebase.initializeApp(firebaseConfig);
var storage = firebase.storage();
  
export default storage;
