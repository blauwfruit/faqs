<?php
/**
*   PageFinder
*
*   Do not copy, modify or distribute this document in any form.
*
*   @author     Matthijs <matthijs@blauwfruit.nl>
*   @copyright  Copyright (c) 2013-2024 blauwfruit (https://blauwfruit.nl)
*   @license    Proprietary Software
*
*/

class PageFinder
{
    public static function search($query)
    {
        $results = array_merge(
            PageFinder::findCMS($query),
            PageFinder::findProduct($query),
            PageFinder::findCategory($query),
            PageFinder::findHome($query)
        );

        $response = [];
        foreach ($results as &$result) {
            $response[] = [
                'data' => $result['object_id'],
                'label' => $result['name'],
                'category' => ucfirst($result['object_type']) // Capitalize the first letter
            ];
        }

        return Tools::jsonEncode($response);
    }

    public static function findProduct($query)
    {
        $results = [];

        $products = Product::searchByName(Context::getContext()->language->id, $query);

        if (!is_array($products)) {
            return [];
        }

        foreach ($products as $key => $product) {
            if (!$product['active']) {
                continue;
            }

            $results[] = [
                'object_type' => 'product',
                'object_id' => $product['id_product'],
                'name' => $product['name'],
            ];
        }

        return $results;
    }

    public static function findCategory($query)
    {
        $results = [];
        foreach (Category::searchByName(Context::getContext()->language->id, $query) as $key => $category) {
            if (!$category['active']) {
                continue;
            }

            $results[] = [
                'object_type' => 'category',
                'object_id' => $category['id_category'],
                'name' => str_replace(array_keys($category), array_values($category), 'name (id_category)')
            ];
        }
        return $results;
    }

    public static function findCMS($query)
    {
        $sql = sprintf(
            'SELECT id_cms, meta_title 
            FROM %scms_lang 
            WHERE meta_title 
            LIKE "%s" 
                AND id_lang = %d 
                AND id_shop = %d
            GROUP BY id_cms, id_shop',
            _DB_PREFIX_,
            '%'.pSQL($query).'%',
            Context::getContext()->language->id,
            Context::getContext()->shop->id
        );
        $results = Db::getInstance()->ExecuteS($sql);

        foreach ($results as &$cms) {
            $cms['object_type'] = 'cms';
            $cms['object_id'] = $cms['id_cms'];
            $cms['name'] = $cms['meta_title'];
            unset($cms['meta_title'], $cms['id_cms']);
        }
        return $results;
    }

    public static function findHome($query)
    {
        if (strpos('home page', strtolower($query)) > -1) {
            return [
                [
                    'object_type' => 'Home',
                    'object_id' => 0,
                    'name' => 'Home page',
                ]
            ];
        }

        return [];
    }
}
