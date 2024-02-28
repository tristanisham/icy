<?php

namespace Tristan\Icy;

enum DepMapOutput
{
    case JSON;
    case PHP;
    case STDOUT;
}