<html>
    <head>
        <title>HTML Audio Player</title>

        <style>
            /*https://css-tricks.com/lets-create-a-custom-audio-player/*/
            .player-button {
                text-align: center;
            }

            td button {
                font-size: 35px;
                height: 80px;
                width: 120px;
            }

            .roller {
                width: 100%;
                height: 55px;
                position: relative;
                overflow: hidden;
                display: flex;
                justify-content: center;
                align-items: center;
                font-size: 45px;
                color: #CACACA;
            }

            .animate {
                position: absolute;
                word-break: break-all;
                top: 0;
                animation: slide 10s infinite;
                animation-direction: alternate-reverse;
                animation-timing-function: linear;
            }
            @keyframes slide {
                from {
                    margin-left: -50%;
                }
                to {
                    margin-left: 0%;
                }
            }

            body {
                margin: 30px;
                padding: 0;
                background: #ddd;
                font-family: Arial, Helvetica, sans-serif;
            }

            .player-main-wrapper {
                width: 100%;
                max-width: 900px;
                min-width: 440px;
                background: #fff;
                margin: 0 auto;
                height: 85vh;
            }

            .player-container {
                position: relative;
                padding-bottom: 23%;
                padding-top: 10px;
                height: 0;
                width: 100%;
                background: #222;
            }

            .playlist-container {
                width: 100%;
                height: 74vh;
                overflow: hidden;
                background: #ddd;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                scroll-behavior: smooth;
            }

            .playlist-container:hover, .playlist-container:focus {
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                scroll-behavior: smooth;
            }

            ol#playlist {
                margin: 0;
                padding: 0;
            }

            ol#playlist li {
                list-style: none;
            }

            ol#playlist li a {
                text-decoration: none;
                height: 85px;
                white-space: nowrap;
                display: block;
                padding: 25px;
                overflow: hidden;
            }

            ol#playlist li a:hover {
                background-color: #666666
            }

            .active {
                background-color: #888787;
            }

            .not-active {
                background-color: #222;
            }

            #playlist .desc {
                color: #CACACA;
                font-size: 45px;
                margin-top: 5px;
            }

            .slider {
                -webkit-appearance: none;
                position: relative;
                width: 70%;
                height: 10px;
                margin: 19px 2.5% 2px 2.5%;
                float: left;
                outline: none;
            }

            .slider::before {
                position: absolute;
                content: "";
                top: 0;
                left: 0;
                width: var(--buffered-width);
                height: 10px;
                background-color: #b50000;
                cursor: pointer;
            }

            .slider::-webkit-slider-thumb {
                position: relative;
                box-sizing: content-box;
                -webkit-appearance: none;
                appearance: none;
                width: 40px;
                height: 40px;
                background: #CACACA;
                cursor: pointer;
                border-radius: 50%;
            }

            .time {
                color: #CACACA;
                font-weight: bold;
                font-size: 40px;
            }
        </style>

        <script>
            let audio = null;
            let slider = null;
            let currentTime = null;
            let totalTime = null;
            let playIndex = 0;

            const playlist = [
                <?php
                $uri = $_SERVER["REQUEST_URI"];
                $uri_path = substr($uri, 0, strrpos($uri, "/")) . "/";
                $protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://';
                $files = glob(__DIR__ . "/playlist/" . "*.{mp3}", GLOB_BRACE);
                $base_path = $protocol . $_SERVER["HTTP_HOST"] . $uri_path . "playlist/";
                $path_info = pathinfo($files[0]);
                $first_song = $base_path . rawurlencode($path_info["basename"]);
                foreach ($files as $file) {
                    $path_info = pathinfo($file);
                    echo json_encode(array("path" => $base_path . rawurlencode($path_info["basename"]), "title" => $path_info["filename"])) . ",";
                }
                ?>
            ]

            let updatePositionState = () => {
                navigator.mediaSession.setPositionState( {
                    duration: audio.duration,
                    playbackRate: audio.playbackRate,
                    position: audio.currentTime
                });
            }

            let addPlaylistItem = (item, index) => {
                let ol = document.getElementById("playlist");
                let li = document.createElement("li");

                let a = document.createElement("a");
                a.setAttribute("href", "javascript:void(0);");
                a.setAttribute("class", "not-active");
                a.setAttribute("onClick", "playListItem(" + index + ");");

                let div = document.createElement("div");
                div.setAttribute("class", "desc");
                div.appendChild(document.createTextNode((index + 1) + ". " + playlist[index].title));

                a.appendChild(div);
                li.appendChild(a);
                ol.appendChild(li);
            }

            let fillPlaylist = () => {
                playlist.forEach(addPlaylistItem);
            }

            let reset = async () => {
                await playListItem(0);
            }

            let playListItem = async (index) => {
                playIndex = index;
                audio.src = playlist[playIndex].path;
                navigator.mediaSession.metadata.title = playlist[playIndex].title;
                selectItemFromPlaylist(index);
                await audio.play().then(() => { updatePositionState(); });
            }

            let selectItemFromPlaylist = (index) => {
                removeActive();
                let active = document.querySelector("ol#playlist :nth-child(" + (index + 1) + ")");
                active.firstChild.classList.remove("not-active");
                active.firstChild.classList.add("active");
                active.scrollIntoView(true);
            }

            let removeActive = () => {
                document.querySelectorAll("ol#playlist li").forEach((item) => {
                    item.firstChild.classList.remove("active");
                    item.firstChild.classList.add("not-active");
                });
            }

            let nextTrack = async () => {
                if (playIndex !== playlist.length) {
                    playIndex++;
                } else {
                    playIndex = 0;
                }

                await playListItem(playIndex);
            }

            let previousTrack = async () => {
                if (playIndex !== 0) {
                    playIndex--;
                } else {
                    playIndex = playlist.length - 1;
                }

                await playListItem(playIndex);
            }

            let seekBackward = () => {
                audio.currentTime = audio.currentTime - 10;
                updatePositionState();
            };

            let seekForward = () => {
                audio.currentTime = audio.currentTime + 10;
                updatePositionState();
            };

            let playPause = async () => {
                if (navigator.mediaSession.playbackState === 'playing') {
                    pause();
                } else {
                    await play();
                }
            }

            let play = async () => {
                await audio.play().then(() => updatePositionState());
            };

            let pause = () => {
                audio.pause();
                updatePositionState();
            };

            let calculateTime = (secs) => {
                let minutes = Math.floor(secs / 60);
                let seconds = Math.floor(secs % 60);
                let returnedSeconds = seconds < 10 ? `0${seconds}` : `${seconds}`;
                return `${minutes}:${returnedSeconds}`;
            }

            let setSliderMax = () => {
                slider.max = Math.floor(audio.duration);
            }

            let showRangeProgress = (percent) => {
                slider.style.setProperty("--buffered-width", percent + "%")
            }

            document.addEventListener("DOMContentLoaded", () => {
                fillPlaylist();

                audio = document.querySelector('audio');
                slider = document.querySelector('#seek-slider')
                currentTime = document.querySelector("#current-time");
                totalTime = document.querySelector("#total-time");

                audio.addEventListener('ended', function() {
                    nextTrack();
                });

                audio.addEventListener('play', function() {
                    if (audio.src === "") {
                        reset();
                    }

                    let details = document.querySelector("#details");
                    details.innerHTML = playlist[playIndex].title;
                    navigator.mediaSession.playbackState = 'playing';
                });

                audio.addEventListener('pause', function() {
                    navigator.mediaSession.playbackState = 'paused';
                });

                audio.addEventListener('loadedmetadata', () => {
                    totalTime.textContent = calculateTime(audio.duration);
                    setSliderMax();
                });

                audio.addEventListener('timeupdate', () => {
                    slider.value = Math.floor(audio.currentTime);
                    currentTime.textContent = calculateTime(audio.currentTime);
                    let buffered = audio.buffered.end(audio.buffered.length - 1);
                    let percent = Math.floor((buffered / audio.duration) * 100);
                    showRangeProgress(percent);
                });

                slider.addEventListener('change', () => {
                    audio.currentTime = slider.value;
                });

                if ( 'mediaSession' in navigator ) {
                    navigator.mediaSession.metadata = new MediaMetadata({
                        title: 'Playlist',
                        artist: 'Various',
                        album: 'Various'
                    });

                    navigator.mediaSession.setActionHandler('pause', pause);
                    navigator.mediaSession.setActionHandler('play', play);
                    navigator.mediaSession.setActionHandler('previoustrack', previousTrack);
                    navigator.mediaSession.setActionHandler('nexttrack', nextTrack);
                    navigator.mediaSession.setActionHandler('seekbackward', seekBackward);
                    navigator.mediaSession.setActionHandler('seekforward', seekForward);
                    navigator.mediaSession.setActionHandler('seekto', (details) => {
                        audio.currentTime = details.seekTime;
                        updatePositionState();
                    });
                }
            });
        </script>
    </head>
    <body>
        <div class="player-main-wrapper clearfix">
            <div id="playlist-container" class="playlist-container">
                <ol id="playlist"></ol>
            </div>

            <div class="player-container">
                <table style="width: 100%">
                    <tr style="display: none">
                        <td colspan="5">
                            <audio id="player" controls></audio>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="5">
                            <span id="current-time" class="time">0:00</span>
                            <span id="current-time" class="time">/</span>
                            <span id="total-time" class="time">0:00</span>
                            <input id="seek-slider" type="range" class="slider" max="100" value="0" />
                        </td>
                    </tr>

                    <tr>
                        <td colspan="5">
                            <div class="roller">
                                <div id="details" class="animate"></div>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="player-button"><button onclick="previousTrack()">&#x23EE;</button></td>
                        <td class="player-button"><button onclick="seekBackward()">&#x23EA;</button></td>
                        <td class="player-button"><button onclick="playPause()">&#x23EF;</button></td>
                        <td class="player-button"><button onclick="seekForward()">&#x23E9;</button></td>
                        <td class="player-button"><button onclick="nextTrack()">&#x23ED;</button></td>
                    </tr>
                </table>
            </div>
        </div>
    </body>
</html>