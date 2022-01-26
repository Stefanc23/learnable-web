import React, { useState, useEffect } from "react";
import { avatarImage } from "@/images";
import storage from "@/firebase";

export default function AttendeeAvatar({ attendee }) {
    const [avatar, setAvatar] = useState(null);
    const [loaded, setLoaded] = useState(false);

    useEffect(() => {
        if (attendee.profile_image_file_path == null) {
            setAvatar(avatarImage);
        } else {
            storage
                .ref(attendee.profile_image_file_path)
                .getDownloadURL()
                .then((url) => {
                    setAvatar(url);
                });
        }
    });

    return (
        <img
            class="object-cover w-8 h-8 rounded-full mr-4"
            style={loaded ? {} : { visibility: "hidden" }}
            src={avatar}
            alt="Profile image"
            onLoad={() => setLoaded(true)}
        />
    );
}
