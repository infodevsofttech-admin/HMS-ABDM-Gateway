<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        return $this->response->setBody('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>ABDM Bridge Gateway</title><style>body{font-family:Arial,sans-serif;margin:40px;line-height:1.5}code{background:#f4f4f4;padding:2px 4px;border-radius:4px}</style></head><body><h1>ABDM Bridge Gateway</h1><p>Gateway M1 is online.</p><p>Use <code>/api/v3/health</code> for a JSON health check.</p></body></html>');
    }

    public function notFound()
    {
        return $this->respondError('Resource not found', 404);
    }
}
