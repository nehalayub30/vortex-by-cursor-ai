jQuery(document).ready(function($) {
    // Initialize swiping functionality
    function initSwipe() {
        var $container = $('.vortex-swipe-container');
        var $items = $container.find('.vortex-swipe-item');
        var currentIndex = 0;
        
        // Hide all items except the first one
        $items.not(':first').hide();
        
        // Touch/mouse events for mobile and desktop
        setupSwipeEvents($items.eq(currentIndex));
        
        // Button click handlers
        $container.on('click', '.vortex-swipe-button.accept', function() {
            handleSwipeAction('accept', $items.eq(currentIndex));
        });
        
        $container.on('click', '.vortex-swipe-button.reject', function() {
            handleSwipeAction('reject', $items.eq(currentIndex));
        });
        
        // Setup swipe events for touch and mouse
        function setupSwipeEvents($item) {
            var startX, startY;
            var isDragging = false;
            var threshold = 100; // Min distance to be considered a swipe
            
            // Touch events
            $item.on('touchstart', function(e) {
                startX = e.originalEvent.touches[0].clientX;
                startY = e.originalEvent.touches[0].clientY;
                isDragging = true;
            });
            
            $item.on('touchmove', function(e) {
                if (!isDragging) return;
                
                var currentX = e.originalEvent.touches[0].clientX;
                var currentY = e.originalEvent.touches[0].clientY;
                var diffX = currentX - startX;
                
                // Horizontal movement is greater than vertical - likely a horizontal swipe
                if (Math.abs(diffX) > Math.abs(currentY - startY)) {
                    e.preventDefault(); // Prevent scrolling
                    $(this).css('transform', 'translateX(' + diffX + 'px)');
                }
            });
            
            $item.on('touchend touchcancel', function(e) {
                if (!isDragging) return;
                
                var currentX = e.originalEvent.changedTouches[0].clientX;
                var diffX = currentX - startX;
                
                handleSwipeEnd(diffX, $(this));
                isDragging = false;
            });
            
            // Mouse events (for desktop)
            $item.on('mousedown', function(e) {
                startX = e.clientX;
                isDragging = true;
                e.preventDefault();
            });
            
            $(document).on('mousemove', function(e) {
                if (!isDragging) return;
                
                var diffX = e.clientX - startX;
                $item.css('transform', 'translateX(' + diffX + 'px)');
            });
            
            $(document).on('mouseup mouseleave', function(e) {
                if (!isDragging) return;
                
                var diffX = e.clientX - startX;
                handleSwipeEnd(diffX, $item);
                isDragging = false;
            });
        }
        
        // Handle the end of a swipe
        function handleSwipeEnd(diffX, $item) {
            if (Math.abs(diffX) > threshold) {
                // Swipe was long enough
                if (diffX > 0) {
                    // Swipe right - accept
                    $item.addClass('swiped-right');
                    setTimeout(function() {
                        handleSwipeAction('accept', $item);
                    }, 300);
                } else {
                    // Swipe left - reject
                    $item.addClass('swiped-left');
                    setTimeout(function() {
                        handleSwipeAction('reject', $item);
                    }, 300);
                }
            } else {
                // Not a long enough swipe - reset position
                $item.css('transform', '');
            }
        }
        
        // Handle swipe action (accept/reject)
        function handleSwipeAction(action, $item) {
            var itemId = $item.data('item-id');
            
            // Show loading state
            $item.addClass('loading');
            
            // Send AJAX request
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_handle_swipe',
                    nonce: vortex_ajax.nonce,
                    item_id: itemId,
                    swipe_action: action
                },
                success: function(response) {
                    if (response.success) {
                        // Move to next item
                        showNextItem();
                    } else {
                        // Show error
                        showMessage($item.find('.vortex-message'), response.data.message, 'error');
                        // Reset position
                        $item.removeClass('swiped-left swiped-right loading').css('transform', '');
                    }
                },
                error: function() {
                    // Show error message
                    showMessage($item.find('.vortex-message'), 'An error occurred. Please try again.', 'error');
                    // Reset position
                    $item.removeClass('swiped-left swiped-right loading').css('transform', '');
                }
            });
        }
        
        // Show next item in the stack
        function showNextItem() {
            // Hide current item
            $items.eq(currentIndex).hide();
            
            // Move to next item
            currentIndex++;
            
            // Check if there are more items
            if (currentIndex < $items.length) {
                // Show next item
                var $nextItem = $items.eq(currentIndex).show();
                
                // Reset transform and swipe classes
                $nextItem.removeClass('swiped-left swiped-right').css('transform', '');
                
                // Setup swipe events for the new item
                setupSwipeEvents($nextItem);
            } else {
                // No more items
                $container.html('<p>No more items to swipe.</p>');
            }
        }
    }
    
    // Show message helper function
    function showMessage($container, message, type) {
        $container.removeClass('vortex-message-success vortex-message-error')
            .addClass('vortex-message-' + type)
            .html(message)
            .show();
        
        setTimeout(function() {
            $container.fadeOut();
        }, 5000);
    }
    
    // Initialize swiping when container is present
    if ($('.vortex-swipe-container').length) {
        initSwipe();
    }
}); 