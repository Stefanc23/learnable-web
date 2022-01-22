import React from "react";
import { Link, Head } from "@inertiajs/inertia-react";
import ApplicationLogo from "@/Components/ApplicationLogo";
import { heroImage } from "@/images";

export default function Welcome(props) {
    const layoutStyle = {
        backgroundImage: "url('/images/bg-pattern.png')",
        backgroundSize: "cover",
    };

    return (
        <>
            <Head title="Welcome" />
            <div className="min-h-screen" style={layoutStyle}>
                <header className="fixed top-0 left-0 w-full">
                    <nav className="bg-transparent border-none">
                        <div className="flex justify-between items-center w-full px-4 sm:px-6 lg:px-8 py-4">
                            <div className="flex">
                                <div className="shrink-0 flex items-center">
                                    <Link href="/">
                                        <ApplicationLogo className="block h-12 w-auto" />
                                    </Link>
                                </div>
                            </div>
                            <div className="flex justify-center items-center">
                                {props.auth.user ? (
                                    <Link
                                        href={route("dashboard")}
                                        className="px-6 py-2 bg-primary hover:bg-primary-400 border border-transparent rounded-3xl text-sm text-white active:bg-primary transition ease-in-out duration-150"
                                    >
                                        Dashboard
                                    </Link>
                                ) : (
                                    <Link
                                        href={route("login")}
                                        className="px-6 py-2 bg-primary hover:bg-primary-400 border border-transparent rounded-3xl text-sm text-white tracking-widest active:bg-primary transition ease-in-out duration-150"
                                    >
                                        Get Started
                                    </Link>
                                )}
                            </div>
                        </div>
                    </nav>
                </header>

                <main>
                    <div className="w-full h-screen flex lg:flex-row flex-col md:justify-between justify-center items-center md:px-16 px-4 md:py-0 py-24">
                        <div className="flex flex-col text-center md:text-left">
                            <h1 className="md:text-8xl text-4xl text-shadow-xl font-serif drop-shadow-xl text-primary">
                                Learnable
                            </h1>
                            <h2 className="md:text-2xl text-xl text-shadow-lg font-serif drop-shadow-xl mt-4">
                                Make learning easier and more practical
                            </h2>
                            <div className="flex mt-8 md:px-0 md:flex-row flex-col px-24">
                                <Link
                                    href={route("login")}
                                    className="bg-primary-600 text-white hover:bg-primary-400 px-8 py-4 rounded text-sm"
                                >
                                    Get Started
                                </Link>
                                <a
                                    href="https://github.com/Stefanc23/learnable-mobile"
                                    target="_blank"
                                    className="bg-primary-600 text-white hover:bg-primary-400 px-8 py-4 rounded text-sm md:ml-4 ml-0 md:mt-0 mt-4"
                                >
                                    Download App
                                </a>
                            </div>
                        </div>
                        <img
                            className="mt-16 md:w-5/12 w-full h-auto"
                            src={heroImage}
                            alt="Hero image"
                        />
                    </div>
                </main>
            </div>
        </>
    );
}
