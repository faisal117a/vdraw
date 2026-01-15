/**
 * Voice to Code Feature (Phase 8)
 * Handles Microphone recording and backend communication.
 */

let pvMediaRecorder;
let pvAudioChunks = [];
let pvIsRecording = false;
let pvStartTime = 0;

window.toggleVoiceRecording = async function () {
    const btn = document.getElementById('pyviz-btn-mic');

    if (pvIsRecording) {
        // Stop
        if (pvMediaRecorder && pvMediaRecorder.state === 'recording') {
            pvMediaRecorder.stop();
        }
        pvIsRecording = false;
        // UI Reset happens in onstop
    } else {
        // Start
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            pvMediaRecorder = new MediaRecorder(stream);
            pvAudioChunks = [];

            pvMediaRecorder.ondataavailable = event => {
                pvAudioChunks.push(event.data);
            };

            pvMediaRecorder.onstop = async () => {
                const audioBlob = new Blob(pvAudioChunks, { type: 'audio/webm' });
                // Stop all tracks
                stream.getTracks().forEach(track => track.stop());

                // Reset UI
                if (btn) {
                    btn.classList.remove('animate-pulse', 'text-red-500', 'border-red-500');
                    btn.classList.add('text-slate-400');
                    btn.innerHTML = '<i class="fa-solid fa-microphone"></i>';
                }

                const duration = (Date.now() - pvStartTime) / 1000;
                await sendAudioToBackend(audioBlob, duration);
            };

            pvMediaRecorder.start();
            pvIsRecording = true;
            pvStartTime = Date.now();

            // Auto stop after configured time or default 60s
            const maxDuration = (window.PV_CONFIG?.MAX_AUDIO_SECONDS || 60) * 1000;
            setTimeout(() => {
                if (pvIsRecording && pvMediaRecorder.state === 'recording') {
                    pvMediaRecorder.stop();
                    pvIsRecording = false;
                }
            }, maxDuration);

            // UI Update
            if (btn) {
                btn.classList.remove('text-slate-400');
                btn.classList.add('animate-pulse', 'text-red-500', 'border-red-500');
                btn.innerHTML = '<i class="fa-solid fa-stop"></i>';
            }

        } catch (err) {
            console.error("Mic Error:", err);
            alert("Could not access microphone. Please allow permissions.");
        }
    }
}

async function sendAudioToBackend(blob, duration = 0) {
    const aiMsg = document.getElementById('pyviz-ai-message');

    // Optimization: Don't send empty/tiny audio (e.g. immediate stop)
    if (blob.size < 2000) {
        if (aiMsg) aiMsg.innerHTML = '<span class="text-orange-400 text-xs">Audio too short. Ignored.</span>';
        return;
    }

    if (aiMsg) aiMsg.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin text-purple-500 mr-2"></i> Transcribing & Generating...';

    const formData = new FormData();
    formData.append('audio', blob, 'recording.webm');
    formData.append('duration', duration.toFixed(2));

    try {
        // Determine backend URL relative to current location
        // Assuming /vdraw-anti-auto/backend/voice_code.php -> ../../backend/voice_code.php
        const backendUrl = '../../backend/voice_code.php';

        // Timeout Signal (30s max)
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);

        const response = await fetch(backendUrl, {
            method: 'POST',
            body: formData,
            signal: controller.signal
        });
        clearTimeout(timeoutId);

        let data;
        try {
            data = await response.json();
        } catch (e) {
            throw new Error("Invalid backend response");
        }

        if (data.error) {
            if (aiMsg) aiMsg.innerHTML = `<span class="text-red-400">Error: ${data.error}</span>`;
            console.error(data.details || data.error);
        } else if (data.code) {
            if (aiMsg) aiMsg.innerHTML = `<span class="text-green-400">Success! Code appended.</span>`;

            // Detect multiple lines
            const lines = data.code.split('\n');
            lines.forEach(line => {
                // Determine indentation
                const rawLine = line;
                const trimmed = rawLine.trim();

                if (!trimmed) return; // Skip empty

                // Calculate spaces at start
                const leadingSpaces = rawLine.match(/^\s*/)[0].length;
                const indentLevel = Math.floor(leadingSpaces / 4);

                // Simple type inference
                let type = 'logic';
                if (trimmed.includes('print(') || trimmed.includes('input(')) type = 'func';
                if (trimmed.includes('=') && !trimmed.includes('if ') && !trimmed.includes('def ')) type = 'var';
                if (trimmed.startsWith('#')) type = 'comment';

                if (window.addLine) {
                    window.addLine({
                        code: trimmed,
                        type: type,
                        label: 'Voice Code',
                        indent: indentLevel // Respect relative indentation from LLM
                    });
                }
            });
        }
    } catch (e) {
        console.error(e);
        if (aiMsg) aiMsg.innerHTML = `<span class="text-red-400">Connection Failed</span>`;
    }
}
