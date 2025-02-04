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

namespace BrockhausAg\ContaoReleaseStagesBundle\Model;

use Contao\Model;

/**
 * Class ReleaseStagesModel
 *
 * @package BrockhausAg\ContaoReleaseStagesBundle\Model
 */
class ReleaseStagesModel extends Model
{
    protected static $strTable = 'tl_release_stages';

}

