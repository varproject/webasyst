<?php

// return [
//     '*' => 'FrontendAuth'
// ];

// if (!wa()->getUser()->isAuth()) {

//     shopLkPluginNavigation::redirectToAuth();

//     // return shopLkPluginNavigation::getAuthRoutes();
//     return [
//         '*' => 'FrontendAuth'
//     ];
// }

return shopLkPluginNavigation::getRouting();
