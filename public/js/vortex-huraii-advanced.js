/**
 * Advanced HURAII Features - 3D, Animation, and AR/VR
 */
jQuery(document).ready(function($) {
    // Check if required libraries are loaded
    if (typeof THREE === 'undefined' && $('.vortex-3d-viewer').length > 0) {
        // Load Three.js dynamically if needed
        loadScript('https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js', initializeAdvancedFeatures);
    } else {
        // Initialize directly if already loaded or not needed
        initializeAdvancedFeatures();
    }
    
    // Helper function to dynamically load scripts
    function loadScript(url, callback) {
        const script = document.createElement('script');
        script.src = url;
        script.onload = callback;
        document.head.appendChild(script);
    }
    
    // Initialize all advanced features
    function initializeAdvancedFeatures() {
        initUpscaler();
        initAnimator();
        init3DModelCreator();
        initVREnvironmentCreator();
        init3DViewer();
    }
    
    // Initialize upscaling functionality
    function initUpscaler() {
        // Handle upscale form submission
        $('#vortex-upscale-form').submit(function(e) {
            e.preventDefault();
            
            const artworkId = $('#upscale_artwork_select').val();
            if (!artworkId) {
                showError('Please select an artwork to upscale.');
                return;
            }
            
            const factor = $('#upscale_factor').val();
            const method = $('#upscale_method').val();
            const isPrivate = $('#upscale_private').is(':checked');
            
            // Calculate required tokens
            const methods = {
                'standard': { '2x': 5, '4x': 10, '8x': 20 },
                'detail_preserve': { '2x': 7, '4x': 14, '8x': 28 },
                'art_enhance': { '2x': 8, '4x': 16, '8x': 32 },
                'noise_reduce': { '2x': 6, '4x': 12, '8x': 24 }
            };
            
            const tokensRequired = methods[method][factor];
            
            if (userTola < tokensRequired) {
                showError(`You need ${tokensRequired} TOLA tokens for this upscale. Current balance: ${userTola}`);
                return;
            }
            
            // Show loading screen
            $('#vortex-upscale-form').hide();
            $('#vortex-upscale-results').show();
            $('.vortex-upscale-loading').show();
            $('.vortex-upscale-success, .vortex-upscale-error').hide();
            
            // Submit upscale request
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_upscale_artwork',
                    artwork_id: artworkId,
                    factor: factor,
                    method: method,
                    private: isPrivate,
                    nonce: vortex_ajax.huraii_nonce
                },
                dataType: 'json',
                success: function(response) {
                    $('.vortex-upscale-loading').hide();
                    
                    if (response.success) {
                        // Show success screen
                        $('.vortex-upscale-success').show();
                        
                        // Update user's TOLA balance
                        userTola -= tokensRequired;
                        updateTolaDisplay();
                        
                        // Set the image source
                        $('#vortex-upscaled-image').attr('src', response.data.file_path);
                        
                        // Set dimensions
                        $('#vortex-original-dimensions').text(
                            `${response.data.original_size.width} × ${response.data.original_size.height}`
                        );
                        $('#vortex-upscaled-dimensions').text(
                            `${response.data.upscaled_size.width} × ${response.data.upscaled_size.height}`
                        );
                        
                        // Show image comparison if possible
                        initImageComparison();
                    } else {
                        // Show error message
                        $('.vortex-upscale-error').show();
                        $('#vortex-upscale-error-message').text(response.data.message);
                    }
                },
                error: function() {
                    $('.vortex-upscale-loading').hide();
                    $('.vortex-upscale-error').show();
                    $('#vortex-upscale-error-message').text('Server error. Please try again later.');
                }
            });
        });
        
        // Handle try again button
        $('#vortex-upscale-try-again').click(function() {
            $('#vortex-upscale-results').hide();
            $('#vortex-upscale-form').show();
        });
        
        // Handle create another button
        $('#vortex-create-another-upscale').click(function() {
            $('#vortex-upscale-results').hide();
            $('#vortex-upscale-form').show();
        });
        
        // Update token cost display when options change
        $('#upscale_factor, #upscale_method').change(function() {
            const factor = $('#upscale_factor').val();
            const method = $('#upscale_method').val();
            
            const methods = {
                'standard': { '2x': 5, '4x': 10, '8x': 20 },
                'detail_preserve': { '2x': 7, '4x': 14, '8x': 28 },
                'art_enhance': { '2x': 8, '4x': 16, '8x': 32 },
                'noise_reduce': { '2x': 6, '4x': 12, '8x': 24 }
            };
            
            $('#upscale-token-cost').text(methods[method][factor]);
        });
    }
    
    // Initialize animation functionality
    function initAnimator() {
        // Handle animation form submission
        $('#vortex-animate-form').submit(function(e) {
            e.preventDefault();
            
            const artworkId = $('#animation_artwork_select').val();
            if (!artworkId) {
                showError('Please select an artwork to animate.');
                return;
            }
            
            const animationType = $('#animation_type').val();
            const duration = parseInt($('#animation_duration').val());
            const includeAudio = $('#animation_include_audio').is(':checked');
            const audioMood = $('#animation_audio_mood').val();
            const isPrivate = $('#animation_private').is(':checked');
            
            // Calculate token cost
            const typeCosts = {
                'subtle_movement': 15,
                'particle_flow': 20,
                'depth_parallax': 25,
                'ambient_life': 30,
                'cinematic': 40
            };
            
            let tokensRequired = typeCosts[animationType];
            if (includeAudio) {
                tokensRequired += 10; // Additional cost for audio
            }
            
            if (userTola < tokensRequired) {
                showError(`You need ${tokensRequired} TOLA tokens for this animation. Current balance: ${userTola}`);
                return;
            }
            
            // Show loading screen
            $('#vortex-animate-form').hide();
            $('#vortex-animation-results').show();
            $('.vortex-animation-loading').show();
            $('.vortex-animation-success, .vortex-animation-error').hide();
            
            // Start loading animation
            let progress = 0;
            const progressBar = $('.vortex-animation-loading .vortex-progress-fill');
            const stages = [
                'Analyzing artwork',
                'Generating depth map',
                'Creating motion paths',
                'Rendering animation frames'
            ];
            
            if (includeAudio) {
                stages.push('Composing audio');
                stages.push('Finalizing with audio');
            } else {
                stages.push('Finalizing animation');
            }
            
            const stageElement = $('.vortex-animation-stage');
            
            // Simulate progress updates
            const progressInterval = setInterval(function() {
                progress += 5;
                if (progress > 100) {
                    clearInterval(progressInterval);
                    return;
                }
                
                progressBar.css('width', progress + '%');
                
                // Update stage text
                const stageIndex = Math.min(Math.floor(progress / (100 / stages.length)), stages.length - 1);
                stageElement.text(stages[stageIndex]);
            }, 500);
            
            // Submit animation request
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_create_animation',
                    artwork_id: artworkId,
                    animation_type: animationType,
                    duration: duration,
                    include_audio: includeAudio,
                    audio_mood: audioMood,
                    private: isPrivate,
                    nonce: vortex_ajax.huraii_nonce
                },
                dataType: 'json',
                success: function(response) {
                    // Ensure at least 5 seconds of loading display for UX
                    setTimeout(function() {
                        clearInterval(progressInterval);
                        progressBar.css('width', '100%');
                        
                        $('.vortex-animation-loading').hide();
                        
                        if (response.success) {
                            // Show success screen
                            $('.vortex-animation-success').show();
                            
                            // Update user's TOLA balance
                            userTola -= tokensRequired;
                            updateTolaDisplay();
                            
                            // Set the video source
                            const videoPlayer = document.getElementById('vortex-animation-player');
                            videoPlayer.setAttribute('poster', response.data.thumbnail_path);
                            
                            const videoSource = document.getElementById('vortex-animation-source');
                            videoSource.setAttribute('src', response.data.video_path);
                            
                            // If there's audio, show audio controls
                            if (response.data.audio_path) {
                                $('#vortex-animation-audio-controls').show();
                                $('#vortex-animation-audio-source').attr('src', response.data.audio_path);
                            } else {
                                $('#vortex-animation-audio-controls').hide();
                            }
                            
                            // Load the video
                            videoPlayer.load();
                        } else {
                            // Show error message
                            $('.vortex-animation-error').show();
                            $('#vortex-animation-error-message').text(response.data.message);
                        }
                    }, 5000);
                },
                error: function() {
                    setTimeout(function() {
                        clearInterval(progressInterval);
                        $('.vortex-animation-loading').hide();
                        $('.vortex-animation-error').show();
                        $('#vortex-animation-error-message').text('Server error. Please try again later.');
                    }, 3000);
                }
            });
        });
        
        // Handle try again button
        $('#vortex-animation-try-again').click(function() {
            $('#vortex-animation-results').hide();
            $('#vortex-animate-form').show();
        });
        
        // Handle create another button
        $('#vortex-create-another-animation').click(function() {
            $('#vortex-animation-results').hide();
            $('#vortex-animate-form').show();
        });
        
        // Update token cost display when options change
        $('#animation_type, #animation_include_audio').change(function() {
            const animationType = $('#animation_type').val();
            const includeAudio = $('#animation_include_audio').is(':checked');
            
            const typeCosts = {
                'subtle_movement': 15,
                'particle_flow': 20,
                'depth_parallax': 25,
                'ambient_life': 30,
                'cinematic': 40
            };
            
            let cost = typeCosts[animationType];
            if (includeAudio) {
                cost += 10;
            }
            
            $('#animation-token-cost').text(cost);
            
            // Toggle audio mood visibility
            if (includeAudio) {
                $('.animation-audio-options').slideDown(200);
            } else {
                $('.animation-audio-options').slideUp(200);
            }
        });
        
        // Update duration display
        $('#animation_duration').on('input change', function() {
            $('#animation_duration_display').text($(this).val() + ' seconds');
        });
    }
    
    // Initialize 3D model creator functionality
    function init3DModelCreator() {
        // Handle 3D model form submission
        $('#vortex-3d-model-form').submit(function(e) {
            e.preventDefault();
            
            const artworkId = $('#model_artwork_select').val();
            if (!artworkId) {
                showError('Please select an artwork to convert to 3D.');
                return;
            }
            
            const modelType = $('#model_type').val();
            const detailLevel = $('#detail_level').val();
            const fileFormat = $('#file_format').val();
            const isPrivate = $('#model_private').is(':checked');
            
            // Calculate token cost
            const modelCosts = {
                'sculpture': {'standard': 20, 'high': 35, 'ultra': 50},
                'scene': {'standard': 30, 'high': 45, 'ultra': 60},
                'character': {'standard': 25, 'high': 40, 'ultra': 55},
                'environment': {'standard': 35, 'high': 50, 'ultra': 65}
            };
            
            const tokensRequired = modelCosts[modelType][detailLevel];
            
            if (userTola < tokensRequired) {
                showError(`You need ${tokensRequired} TOLA tokens for this 3D model. Current balance: ${userTola}`);
                return;
            }
            
            // Show loading screen
            $('#vortex-3d-model-form').hide();
            $('#vortex-3d-results').show();
            $('.vortex-3d-loading').show();
            $('.vortex-3d-success, .vortex-3d-error').hide();
            
            // Start loading animation
            let progress = 0;
            const progressBar = $('.vortex-3d-loading .vortex-progress-fill');
            const stages = [
                'Analyzing artwork geometry',
                'Creating depth map',
                'Generating 3D mesh',
                'Adding textures',
                'Refining details',
                'Finalizing model'
            ];
            
            const stageElement = $('.vortex-3d-stage');
            
            // Simulate progress updates
            const progressInterval = setInterval(function() {
                progress += 4;
                if (progress > 100) {
                    clearInterval(progressInterval);
                    return;
                }
                
                progressBar.css('width', progress + '%');
                
                // Update stage text
                const stageIndex = Math.min(Math.floor(progress / (100 / stages.length)), stages.length - 1);
                stageElement.text(stages[stageIndex]);
            }, 600);
            
            // Submit 3D model creation request
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_create_3d_model',
                    artwork_id: artworkId,
                    model_type: modelType,
                    detail_level: detailLevel,
                    file_format: fileFormat,
                    private: isPrivate,
                    nonce: vortex_ajax.huraii_nonce
                },
                dataType: 'json',
                success: function(response) {
                    // Ensure at least 8 seconds of loading display for UX
                    setTimeout(function() {
                        clearInterval(progressInterval);
                        progressBar.css('width', '100%');
                        
                        $('.vortex-3d-loading').hide();
                        
                        if (response.success) {
                            // Show success screen
                            $('.vortex-3d-success').show();
                            
                            // Update user's TOLA balance
                            userTola -= tokensRequired;
                            updateTolaDisplay();
                            
                            // Initialize 3D viewer with the model
                            initialize3DViewer(response.data.model_path, response.data.thumbnail_path);
                            
                            // Set up AR link if available
                            if (response.data.ar_link) {
                                $('#vortex-view-ar').show();
                                $('#vortex-ar-direct-link').attr('href', response.data.ar_link).text(response.data.ar_link);
                                
                                // Generate QR code for AR
                                generateQRCode(response.data.ar_link);
                            } else {
                                $('#vortex-view-ar').hide();
                            }
                            
                            // Set download link
                            $('#vortex-download-3d').attr('data-file', response.data.model_path);
                        } else {
                            // Show error message
                            $('.vortex-3d-error').show();
                            $('#vortex-3d-error-message').text(response.data.message);
                        }
                    }, 8000);
                },
                error: function() {
                    setTimeout(function() {
                        clearInterval(progressInterval);
                        $('.vortex-3d-loading').hide();
                        $('.vortex-3d-error').show();
                        $('#vortex-3d-error-message').text('Server error. Please try again later.');
                    }, 3000);
                }
            });
        });
        
        // Handle try again button
        $('#vortex-3d-try-again').click(function() {
            $('#vortex-3d-results').hide();
            $('#vortex-3d-model-form').show();
        });
        
        // Handle create another button
        $('#vortex-create-another-3d').click(function() {
            $('#vortex-3d-results').hide();
            $('#vortex-3d-model-form').show();
        });
        
        // Update token cost display when options change
        $('#model_type, #detail_level').change(function() {
            const modelType = $('#model_type').val();
            const detailLevel = $('#detail_level').val();
            
            const modelCosts = {
                'sculpture': {'standard': 20, 'high': 35, 'ultra': 50},
                'scene': {'standard': 30, 'high': 45, 'ultra': 60},
                'character': {'standard': 25, 'high': 40, 'ultra': 55},
                'environment': {'standard': 35, 'high': 50, 'ultra': 65}
            };
            
            $('#model-token-cost').text(modelCosts[modelType][detailLevel]);
        });
        
        // Handle AR view button
        $('#vortex-view-ar').click(function() {
            $('.vortex-qr-container').toggle();
        });
        
        // Handle 3D download button
        $('#vortex-download-3d').click(function() {
            const modelPath = $(this).attr('data-file');
            if (modelPath) {
                window.location.href = modelPath;
            }
        });
    }
    
    // Initialize VR environment creation
    function initVREnvironmentCreator() {
        // Handle VR environment form submission
        $('#vortex-vr-environment-form').submit(function(e) {
            e.preventDefault();
            
            const artworkId = $('#vr_artwork_select').val();
            if (!artworkId) {
                showError('Please select an artwork for the VR environment.');
                return;
            }
            
            const environmentType = $('#vr_environment_type').val();
            const complexity = $('#vr_complexity').val();
            const platform = $('#vr_platform').val();
            const multiUser = $('#vr_multi_user').is(':checked');
            const isPrivate = $('#vr_private').is(':checked');
            
            // Calculate token cost
            const environmentCosts = {
                'gallery': {'standard': 35, 'complex': 55, 'expansive': 75},
                'immersive': {'standard': 50, 'complex': 70, 'expansive': 90},
                'interactive': {'standard': 60, 'complex': 80, 'expansive': 100}
            };
            
            let tokensRequired = environmentCosts[environmentType][complexity];
            if (multiUser) {
                tokensRequired += 20; // Additional cost for multi-user
            }
            
            if (userTola < tokensRequired) {
                showError(`You need ${tokensRequired} TOLA tokens for this VR environment. Current balance: ${userTola}`);
                return;
            }
            
            // Show loading screen
            $('#vortex-vr-environment-form').hide();
            $('#vortex-immersive-results').show();
            $('.vortex-immersive-loading').show();
            $('.vortex-immersive-success, .vortex-immersive-error').hide();
            
            // Start loading animation
            let progress = 0;
            const progressBar = $('.vortex-immersive-loading .vortex-progress-fill');
            const stages = [
                'Analyzing artwork composition',
                'Creating spatial mapping',
                'Generating 3D assets',
                'Building environment',
                'Adding lighting and effects',
                multiUser ? 'Configuring multi-user capabilities' : 'Optimizing performance',
                'Finalizing environment'
            ];
            
            const stageElement = $('.vortex-immersive-stage');
            
            // Simulate progress updates
            const progressInterval = setInterval(function() {
                progress += 3;
                if (progress > 100) {
                    clearInterval(progressInterval);
                    return;
                }
                
                progressBar.css('width', progress + '%');
                
                // Update stage text
                const stageIndex = Math.min(Math.floor(progress / (100 / stages.length)), stages.length - 1);
                stageElement.text(stages[stageIndex]);
            }, 800);
            
            // Submit VR environment creation request
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_create_vr_environment',
                    artwork_id: artworkId,
                    environment_type: environmentType,
                    complexity: complexity,
                    platform: platform,
                    multi_user: multiUser,
                    private: isPrivate,
                    nonce: vortex_ajax.huraii_nonce
                },
                dataType: 'json',
                success: function(response) {
                    // Ensure at least 10 seconds of loading display for UX
                    setTimeout(function() {
                        clearInterval(progressInterval);
                        progressBar.css('width', '100%');
                        
                        $('.vortex-immersive-loading').hide();
                        
                        if (response.success) {
                            // Show success screen
                            $('.vortex-immersive-success').show();
                            $('#vortex-immersive-success-title').text('VR Environment Created!');
                            
                            // Update user's TOLA balance
                            userTola -= tokensRequired;
                            updateTolaDisplay();
                            
                            // Set preview image
                            if (response.data.thumbnail_path) {
                                $('#vortex-vr-preview-image').attr('src', response.data.thumbnail_path).show();
                            }
                            
                            // Set up VR preview if available
                            if (response.data.preview_path) {
                                $('#vortex-vr-preview-video').attr('src', response.data.preview_path).show();
                                $('#vortex-vr-video-container').show();
                            } else {
                                $('#vortex-vr-video-container').hide();
                            }
                            
                            // Set up VR launch link
                            $('#vortex-launch-vr').attr('href', response.data.vr_link);
                            $('#vortex-vr-platform-name').text(response.data.platform_name);
                            
                            // Generate QR code for VR if applicable
                            if (response.data.platform === 'webxr' || response.data.platform === 'oculus') {
                                generateQRCode(response.data.vr_link, 'vortex-vr-qr-code');
                                $('.vortex-vr-qr-container').show();
                            } else {
                                $('.vortex-vr-qr-container').hide();
                            }
                        } else {
                            // Show error message
                            $('.vortex-immersive-error').show();
                            $('#vortex-immersive-error-message').text(response.data.message);
                        }
                    }, 10000);
                },
                error: function() {
                    setTimeout(function() {
                        clearInterval(progressInterval);
                        $('.vortex-immersive-loading').hide();
                        $('.vortex-immersive-error').show();
                        $('#vortex-immersive-error-message').text('Server error. Please try again later.');
                    }, 3000);
                }
            });
        });
        
        // Handle try again button
        $('#vortex-immersive-try-again').click(function() {
            $('#vortex-immersive-results').hide();
            $('#vortex-vr-environment-form').show();
        });
        
        // Handle create another button
        $('#vortex-create-another-vr').click(function() {
            $('#vortex-immersive-results').hide();
            $('#vortex-vr-environment-form').show();
        });
        
        // Update token cost display when options change
        $('#vr_environment_type, #vr_complexity, #vr_multi_user').change(function() {
            const environmentType = $('#vr_environment_type').val();
            const complexity = $('#vr_complexity').val();
            const multiUser = $('#vr_multi_user').is(':checked');
            
            const environmentCosts = {
                'gallery': {'standard': 35, 'complex': 55, 'expansive': 75},
                'immersive': {'standard': 50, 'complex': 70, 'expansive': 90},
                'interactive': {'standard': 60, 'complex': 80, 'expansive': 100}
            };
            
            let cost = environmentCosts[environmentType][complexity];
            if (multiUser) {
                cost += 20;
            }
            
            $('#vr-token-cost').text(cost);
        });
    }
    
    // Initialize 3D viewer with Three.js
    function init3DViewer() {
        if (!$('#vortex-3d-viewer').length) return;
        
        // This will be initialized when a 3D model is created
    }
    
    // Set up and render 3D model in viewer
    function initialize3DViewer(modelPath, fallbackImage) {
        if (typeof THREE === 'undefined' || !$('#vortex-3d-viewer').length) {
            // If Three.js isn't available, just show the fallback image
            $('#vortex-3d-viewer').html(`<img src="${fallbackImage}" alt="3D Model Preview" class="vortex-3d-fallback-image">`);
            return;
        }
        
        // Clear viewer
        $('#vortex-3d-viewer').empty();
        
        // Create scene, camera, renderer
        const container = document.getElementById('vortex-3d-viewer');
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
        
        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setClearColor(0x000000, 0);
        container.appendChild(renderer.domElement);
        
        // Add ambient light
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
        scene.add(ambientLight);
        
        // Add directional light
        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(5, 5, 5);
        scene.add(directionalLight);
        
        // Add controls
        const controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.1;
        
        // Position camera
        camera.position.z = 5;
        
        // Load the model
        const loader = new THREE.GLTFLoader();
        
        // Add loading indicator
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'vortex-3d-loading-indicator';
        loadingDiv.innerHTML = '<div class="vortex-spinner"></div><p>Loading 3D Model...</p>';
        container.appendChild(loadingDiv);
        
        // Attempt to load the model
        loader.load(
            modelPath,
            function(gltf) {
                // Remove loading indicator
                container.removeChild(loadingDiv);
                
                // Add model to scene
                scene.add(gltf.scene);
                
                // Auto-center and scale model
                const box = new THREE.Box3().setFromObject(gltf.scene);
                const center = box.getCenter(new THREE.Vector3());
                const size = box.getSize(new THREE.Vector3());
                
                // Center model
                gltf.scene.position.x = -center.x;
                gltf.scene.position.y = -center.y;
                gltf.scene.position.z = -center.z;
                
                // Scale model to fit view
                const maxDim = Math.max(size.x, size.y, size.z);
                const scale = 3 / maxDim;
                gltf.scene.scale.set(scale, scale, scale);
                
                // Animation function
                function animate() {
                    requestAnimationFrame(animate);
                    controls.update();
                    renderer.render(scene, camera);
                }
                
                animate();
            },
            function(xhr) {
                // Progress
                const percent = Math.floor((xhr.loaded / xhr.total) * 100);
                loadingDiv.innerHTML = `<div class="vortex-spinner"></div><p>Loading 3D Model... ${percent}%</p>`;
            },
            function(error) {
                // Error loading model
                container.removeChild(loadingDiv);
                container.innerHTML = `<div class="vortex-3d-error-message">
                    <p>Error loading 3D model. Displaying preview image instead.</p>
                    <img src="${fallbackImage}" alt="3D Model Preview" class="vortex-3d-fallback-image">
                </div>`;
                console.error('Error loading 3D model:', error);
            }
        );
        
        // Handle window resize
        window.addEventListener('resize', function() {
            camera.aspect = container.clientWidth / container.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(container.clientWidth, container.clientHeight);
        });
        
        // Connect control buttons
        $('#vortex-rotate-left').click(function() {
            controls.rotateLeft(Math.PI / 8);
        });
        
        $('#vortex-rotate-right').click(function() {
            controls.rotateLeft(-Math.PI / 8);
        });
        
        $('#vortex-zoom-in').click(function() {
            camera.position.z -= 0.5;
        });
        
        $('#vortex-zoom-out').click(function() {
            camera.position.z += 0.5;
        });
        
        $('#vortex-reset-view').click(function() {
            camera.position.set(0, 0, 5);
            controls.reset();
        });
    }
    
    // Generate QR Code
    function generateQRCode(url, elementId = 'vortex-ar-qr-code') {
        if (!$('#' + elementId).length) return;
        
        // Clear existing QR code
        $('#' + elementId).empty();
        
        // Generate new QR code
        new QRCode(document.getElementById(elementId), {
            text: url,
            width: 180,
            height: 180,
            colorDark: '#4A26AB',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
    }
    
    // Initialize image comparison slider
    function initImageComparison() {
        const slider = document.querySelector('.vortex-comparison-slider');
        if (!slider) return;
        
        const img = slider.querySelector('img');
        const handle = slider.querySelector('.vortex-comparison-handle');
        
        // Set initial position
        const initialPosition = 50;
        img.style.clipPath = `inset(0 0 0 ${initialPosition}%)`;
        handle.style.left = `${initialPosition}%`;
        
        // Add event listeners
        slider.addEventListener('mousedown', startDrag);
        slider.addEventListener('touchstart', startDrag, { passive: true });
        
        function startDrag(e) {
            e.preventDefault();
            document.addEventListener('mousemove', drag);
            document.addEventListener('touchmove', drag, { passive: false });
            document.addEventListener('mouseup', stopDrag);
            document.addEventListener('touchend', stopDrag);
        }
        
        function drag(e) {
            let position;
            if (e.type === 'touchmove') {
                const touch = e.touches[0];
                position = (touch.clientX - slider.getBoundingClientRect().left) / slider.offsetWidth * 100;
            } else {
                position = (e.clientX - slider.getBoundingClientRect().left) / slider.offsetWidth * 100;
            }
            
            position = Math.max(0, Math.min(100, position));
            
            img.style.clipPath = `inset(0 0 0 ${position}%)`;
            handle.style.left = `${position}%`;
        }
        
        function stopDrag() {
            document.removeEventListener('mousemove', drag);
            document.removeEventListener('touchmove', drag);
            document.removeEventListener('mouseup', stopDrag);
            document.removeEventListener('touchend', stopDrag);
        }
    }
}); 