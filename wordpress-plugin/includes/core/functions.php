<?php
// Core plugin functions

function vortex_ai_init() {
    return VortexAI\Core\Plugin::getInstance();
}

function vortex_ai_manager() {
    return VortexAI\AI\Manager::getInstance();
}
