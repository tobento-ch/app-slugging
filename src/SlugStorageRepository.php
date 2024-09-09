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

use Tobento\Service\Repository\Storage\StorageRepository;
use Tobento\Service\Repository\Storage\Column\ColumnsInterface;
use Tobento\Service\Repository\Storage\Column\ColumnInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Slugifier\SlugInterface;

class SlugStorageRepository extends StorageRepository implements SlugRepositoryInterface
{
    /**
     * Returns the configured columns.
     *
     * @return iterable<ColumnInterface>|ColumnsInterface
     */
    protected function configureColumns(): iterable|ColumnsInterface
    {
        return [
            Column\Id::new(),
            Column\Text::new('slug'),
            Column\Text::new('locale', type: 'char')->type(length: 5),
            Column\Text::new('resource_key'),
            Column\Text::new('resource_id'),
        ];
    }
    
    /**
     * Save the given slug.
     *
     * @param SlugInterface $slug
     * @return SlugInterface
     */
    public function saveSlug(SlugInterface $slug): SlugInterface
    {
        $entity = $this->findOne(where: ['slug' => $slug->slug(), 'locale' => $slug->locale()]);
        
        if (is_null($entity)) {
            $this->create([
                'slug' => $slug->slug(),
                'locale' => $slug->locale(),
                'resource_key' => $slug->resourceKey(),
                'resource_id' => $slug->resourceId(),
            ]);
            
            return $slug;
        }
        
        $this->updateById(
            id: $entity->get('id'),
            attributes: [
                'resource_key' => $slug->resourceKey(),
                'resource_id' => $slug->resourceId(),
            ],
        );
        
        return $slug;
    }
    
    /**
     * Delete the given slug.
     *
     * @param SlugInterface $slug
     * @return SlugInterface
     */
    public function deleteSlug(SlugInterface $slug): SlugInterface
    {
        $this->delete(where: ['slug' => $slug->slug(), 'locale' => $slug->locale()]);
        
        return $slug;
    }
}