<?php

/*
 * html/controllers/data_explorer/get_ak_plot.php
 *
 * Glue code to call DataExplorer::get_ak_plot(). All exceptions go to the
 * global exception handler.
 */

$user = \xd_security\detectUser(
    array(XDUser::INTERNAL_USER, XDUser::PUBLIC_USER)
);

$m = new \DataWarehouse\Access\DataExplorer($_REQUEST);

$result = $m->get_ak_plot($user);

foreach ($result['headers'] as $k => $v) {
    header($k . ": " . $v);
}

echo $result['results'];
