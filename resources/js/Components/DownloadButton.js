import React, { useState, useEffect } from "react";
import storage from "@/firebase";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faDownload } from "@fortawesome/free-solid-svg-icons";

export default function DownloadButton({ filePath }) {
    const [url, setUrl] = useState(null);

    useEffect(() => {
        storage
            .ref(filePath)
            .getDownloadURL()
            .then((url) => {
                setUrl(url);
            });
    }, [filePath]);

    return (
        <a href={url} target="_blank">
            <FontAwesomeIcon
                icon={faDownload}
                size="lg"
                className="text-primary"
            />
        </a>
    );
}
