<?php

if (wa()->getEnv() !== 'frontend') {
    return array();
}

return shopLkPluginRoutingProvider::getRouting();
