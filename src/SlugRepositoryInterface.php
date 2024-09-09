<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Slugging;

use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Slugifier\SlugInterface;

interface SlugRepositoryInterface extends RepositoryInterface
{
    /**
     * Save the given slug.
     *
     * @param SlugInterface $slug
     * @return SlugInterface
     */
    public function saveSlug(SlugInterface $slug): SlugInterface;
    
    /**
     * Delete the given slug.
     *
     * @param SlugInterface $slug
     * @return SlugInterface
     */
    public function deleteSlug(SlugInterface $slug): SlugInterface;
}