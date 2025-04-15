<script>
$(document).ready(function() {
    // Connect to MetaMask
    $('#vortex-connect-wallet').click(function() {
        $('#vortex-connect-wallet').html('Connecting...').prop('disabled', true);
        web3.eth.requestAccounts()
            .then(function(accounts) {
                console.log('Connected to MetaMask');
                $('#vortex-connect-wallet').html('Connected').prop('disabled', true);
            })
            .catch(function(error) {
                console.error(error);
                alert('Error connecting to MetaMask: ' + error.message);
                $('#vortex-connect-wallet').html('Connect Wallet').prop('disabled', false);
            });
    });
    
    // Close modal when clicking outside the content
    $(window).click(function(e) {
        if ($(e.target).is('#agreement-modal')) {
            $('#agreement-modal').hide();
        }
    });
});
</script> 