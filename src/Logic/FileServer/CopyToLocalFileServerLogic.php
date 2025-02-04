<?php

declare(strict_types=1);

/*
 * This file is part of contao-release-stages-bundle.
 *
 * (c) BROCKHAUS AG 2022 <info@brockhaus-ag.de>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/brockhaus-ag/contao-release-stages-bundle
 */

namespace BrockhausAg\ContaoReleaseStagesBundle\Logic\FileServer;

use Exception;

class CopyToLocalFileServerLogic {
    public function createDirectory(string $directory) : void
    {
        if (file_exists($directory)) {
            return;
        }

        if (!@mkdir($directory)) {
            $error = error_get_last();
            throw new Exception(sprintf("mkdir(%s) failed: %s", $directory, $error['message']));
        }
    }

    public function getLastModifiedTimeFromFile(string $file) : int
    {
        return filemtime($file);
    }

    public function copy(array $file) : void
    {
        if (!copy($file["path"], $file["prodPath"])) {
            $errors = error_get_last();
            echo "COPY ERROR: ".$errors['type'];
            echo "<br />\n".$errors['message'];
        }
    }

    public function delete(string $file, string $path) : void
    {
        $file = str_replace("files", "", $file);
        $file = $path. $file;
        if (file_exists($file)) {
            if (!unlink($file)) {
                $error = error_get_last();
                die("Fehler beim Löschen der Datei: \"". $file. "\"</br>rm error: ". $error['message']);
            }
        }
    }
}
