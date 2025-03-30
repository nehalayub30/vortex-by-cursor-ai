/**
 * Delete image from history
 */
function deleteImage(imageId, $item) {
    var loadingText = vortexImageGenerator.i18n.deleting;
    
    if ($item) {
        $item.addClass('loading').append('<div class="loading-overlay"><span>' + loadingText + '</span></div>');
    }
    
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'vortex_delete_image',
            nonce: vortexImageGenerator.nonce,
            image_id: imageId
        },
        success: function(response) {
            if (response.success) {
                if ($item) {
                    $item.fadeOut(300, function() {
                        $item.remove();
                        
                        // Check if the history item is now empty
                        var $historyItem = $item.closest('.history-item');
                        if ($historyItem.find('.history-image').length <= 1) {
                            $historyItem.fadeOut(300, function() {
                                $historyItem.remove();
                                
                                // Check if history is now empty
                                if ($('#history-items .history-item').length === 0) {
                                    $('#history-items').html('<div class="empty-history"><p>No generated images yet. Create your first image in the Generate tab.</p></div>');
                                }
                            });
                        }
                    });
                }
            } else {
                if ($item) {
                    $item.removeClass('loading').find('.loading-overlay').remove();
                }
                alert(vortexImageGenerator.i18n.error + ' ' + response.data.message);
            }
        },
        error: function(xhr, status, error) {
            if ($item) {
                $item.removeClass('loading').find('.loading-overlay').remove();
            }
            alert(vortexImageGenerator.i18n.error + ' ' + error);
        }
    });
}

/**
 * Download image
 */
function downloadImage(url, filename) {
    var link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Open image modal
 */
function openImageModal(imageUrl, imageId, prompt) {
    modalImageData = {
        url: imageUrl,
        id: imageId,
        prompt: prompt
    };
    
    $('#modal-image').attr('src', imageUrl);
    $('#image-preview-modal').show();
}

/**
 * Close image modal
 */
function closeImageModal() {
    $('#image-preview-modal').hide();
    modalImageData = null;
}

/**
 * Format date
 */
function formatDate(dateString) {
    var date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

})(jQuery); 