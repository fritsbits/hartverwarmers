<?php

namespace App\Services\FicheStorage;

use RuntimeException;

/**
 * Too many zips are being built right now, or the server is short on disk
 * space for another one. Temporary: the visitor can retry shortly.
 */
class DownloadBusy extends RuntimeException {}
