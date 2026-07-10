<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/** Security headers not included in CodeIgniter's built-in filter. */
class SecurityHeaders extends BaseConfig
{
    public bool $hstsEnabled = true;
    public int $hstsMaxAge = 31536000;
    public bool $hstsIncludeSubDomains = false;
    public bool $hstsPreload = false;

    /** Trust this header only when requests come through a trusted proxy. */
    public bool $trustForwardedProto = false;

    public string $permissionsPolicy = 'accelerometer=(), autoplay=(self), camera=(), display-capture=(), encrypted-media=(), fullscreen=(self), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), payment=(), picture-in-picture=(), publickey-credentials-get=(), screen-wake-lock=(self), usb=()';
}
