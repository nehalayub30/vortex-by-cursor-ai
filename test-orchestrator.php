<?php
// Include the orchestrator class
require_once 'includes/class-vortex-thorius-orchestrator.php';

// Create orchestrator instance
$orchestrator = new Vortex_Thorius_Orchestrator();

// Try to call the get_agent_tabs method
try {
    $tabs = $orchestrator->get_agent_tabs();
    echo "Success! Method exists and returned: \n";
    var_export($tabs);
} catch (Error $e) {
    echo "Error: " . $e->getMessage();
}
?> 