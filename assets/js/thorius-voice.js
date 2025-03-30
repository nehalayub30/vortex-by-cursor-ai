/**
 * Thorius Voice Interface
 * 
 * Provides speech recognition and text-to-speech for Thorius
 */
(function($) {
    'use strict';
    
    // Check if speech recognition is supported
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const SpeechGrammarList = window.SpeechGrammarList || window.webkitSpeechGrammarList;
    const SpeechSynthesisUtterance = window.SpeechSynthesisUtterance || window.webkitSpeechSynthesisUtterance;
    
    // Initialize when document is ready
    $(document).ready(function() {
        if (!SpeechRecognition) {
            console.log('Speech recognition not supported');
            $('#vortex-thorius-voice-btn').hide();
            return;
        }
        
        const thorius = window.vortexThorius || {};
        
        // Set up speech recognition
        const recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;
        
        // Set language
        let language = 'en-US';
        if (thorius.userLanguage) {
            language = thorius.mapLanguageCode(thorius.userLanguage);
        }
        recognition.lang = language;
        
        // Create grammar list if available
        if (SpeechGrammarList) {
            const speechGrammarList = new SpeechGrammarList();
            const grammar = '#JSGF V1.0; grammar commands; public <command> = help | search | find | show | create | analyze | recommend | explain; public <object> = art | artwork | nft | event | exhibition | auction | marketplace | blockchain | tola | contract;';
            speechGrammarList.addFromString(grammar, 1);
            recognition.grammars = speechGrammarList;
        }
        
        // Voice button
        const voiceBtn = $('#vortex-thorius-voice-btn');
        const voiceIcon = voiceBtn.find('.vortex-thorius-voice-icon');
        const voiceIndicator = $('#vortex-thorius-voice-indicator');
        
        let isListening = false;
        
        // Start/stop voice recognition
        voiceBtn.on('click', function() {
            if (isListening) {
                stopListening();
            } else {
                startListening();
            }
        });
        
        // Handle recognition results
        recognition.onresult = function(event) {
            const speechResult = event.results[0][0].transcript.trim();
            console.log('Speech recognized:', speechResult);
            
            // Add user message to chat
            $('#vortex-thorius-message-input').val(speechResult);
            
            // Submit the form
            $('#vortex-thorius-message-form').submit();
            
            stopListening();
        };
        
        // Handle recognition errors
        recognition.onerror = function(event) {
            console.error('Speech recognition error:', event.error);
            
            if (event.error === 'no-speech') {
                showVoiceMessage('No speech detected. Please try again.');
            } else if (event.error === 'audio-capture') {
                showVoiceMessage('No microphone detected. Please check your device.');
            } else if (event.error === 'not-allowed') {
                showVoiceMessage('Microphone access denied. Please allow microphone access.');
            } else {
                showVoiceMessage('Speech recognition error. Please try again.');
            }
            
            stopListening();
        };
        
        // Handle recognition end
        recognition.onend = function() {
            stopListening();
        };
        
        // Start listening
        function startListening() {
            try {
                recognition.start();
                isListening = true;
                voiceBtn.addClass('listening');
                voiceIcon.addClass('active');
                voiceIndicator.text('Listening...').show();
                
                // Set timeout to stop listening after 10 seconds
                setTimeout(function() {
                    if (isListening) {
                        recognition.stop();
                    }
                }, 10000);
            } catch (e) {
                console.error('Error starting speech recognition:', e);
                stopListening();
            }
        }
        
        // Stop listening
        function stopListening() {
            try {
                if (isListening) {
                    recognition.stop();
                }
            } catch (e) {
                console.error('Error stopping speech recognition:', e);
            }
            
            isListening = false;
            voiceBtn.removeClass('listening');
            voiceIcon.removeClass('active');
            voiceIndicator.hide();
        }
        
        // Show voice message
        function showVoiceMessage(message) {
            voiceIndicator.text(message).show();
            
            setTimeout(function() {
                voiceIndicator.hide();
            }, 3000);
        }
        
        // Text to speech
        function speakText(text, language) {
            if (!window.speechSynthesis) {
                console.log('Text-to-speech not supported');
                return;
            }
            
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = language || recognition.lang;
            utterance.rate = 1.0;
            utterance.pitch = 1.0;
            
            window.speechSynthesis.speak(utterance);
        }
        
        // Speak Thorius responses if enabled
        $(document).on('thorius:response', function(e, data) {
            if (thorius.voiceOutput && data && data.message) {
                speakText(data.message, recognition.lang);
            }
        });
        
        // Toggle voice output
        $('#vortex-thorius-voice-output-toggle').on('change', function() {
            thorius.voiceOutput = $(this).is(':checked');
            
            // Save preference
            if (window.localStorage) {
                localStorage.setItem('thorius_voice_output', thorius.voiceOutput ? '1' : '0');
            }
            
            // Announce change
            if (thorius.voiceOutput) {
                speakText('Voice output enabled', recognition.lang);
            }
        });
        
        // Initialize voice output setting from saved preference
        if (window.localStorage) {
            const savedSetting = localStorage.getItem('thorius_voice_output');
            thorius.voiceOutput = savedSetting === '1';
            $('#vortex-thorius-voice-output-toggle').prop('checked', thorius.voiceOutput);
        }
    });
    
})(jQuery); 