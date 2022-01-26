import React, { useState, useEffect } from "react";
import { Link } from "@inertiajs/inertia-react";
import { thumbnailImage } from "@/images";
import storage from "@/firebase";
import Skeleton from "react-loading-skeleton";
import "react-loading-skeleton/dist/skeleton.css";

export default function ClassCard({ classroom }) {
    const [thumbnail, setThumbnail] = useState(null);
    const [loaded, setLoaded] = useState(false);

    useEffect(() => {
        if (classroom.banner_image_file_path == null) {
            setThumbnail(thumbnailImage);
        } else {
            storage
                .ref(classroom.banner_image_file_path)
                .getDownloadURL()
                .then((url) => {
                    setThumbnail(url);
                });
        }
    }, [classroom]);

    return (
        <Link href={`/classrooms/${classroom.id}`} className="w-full">
            <div className="rounded-md shadow-sm bg-white w-full">
                <div className="relative rounded-t-lg h-48 w-full">
                    {!loaded && <Skeleton className="absolute top-0 left-0 rounded-t-lg h-48 w-full" />}
                    <img
                        className="absolute top-0 left-0 rounded-t-lg object-cover h-48 w-full"
                        style={loaded ? {} : { visibility: "hidden" }}
                        src={thumbnail}
                        alt={classroom.name}
                        onLoad={() => setLoaded(true)}
                    />
                </div>
                <div className="p-6 text-center">
                    <h5 className="text-gray-900 text-xs font-medium">
                        {classroom.name || <Skeleton />}
                    </h5>
                </div>
            </div>
        </Link>
    );
}
