<?php

namespace CCR\AppKernel;

// ================================================================================
// Resource that application kernels are run on
// ================================================================================
class AKResource
{
    public $id = null;  // Database id
    public $nickname = null;  // Resource nickname (e.g., short name such as "edge")
    public $name = null;  // Resource full name (e.g., edge.ccr.buffalo.edu)
    public $description = null;  // Resource description
    public $enabled = false;  // true if the resoure is enabled
    public $visible = false;  // true if visible outside of the application kernel tools
    public $xdmod_resource_id = null;  // XDMoD resource id
    public mixed $xdmod_cluster_id;
}
