<?php

namespace MPF\Log;

class Category
{
    const NONE = 0;
    const ALL = 1;
    const FRAMEWORK = 2;
    const CONFIG = 4;
    const DATABASE = 8;
    const TEMPLATE = 16;
    const ENVIRONMENT = 32;
    const SERVICE = 64;
    const TEXT = 128;
}
