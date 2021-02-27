<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2015 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

namespace tests\unit;

use atoum;

require_once __DIR__ . '/../../bootstrap.php';
$f = str_replace('\\', '/', __FILE__);
require_once str_replace('tests/unit/', '', $f);

class dcTemplate extends atoum
{
    private function getCore()
    {
        $controller              = new \atoum\atoum\mock\controller();
        $controller->__construct = function () {};

        $core       = new \mock\dcCore(null, null, null, null, null, null, $controller);
        $core->blog = json_decode('{"settings": {"system": {"tpl_allow_php":false,"tpl_use_cache":true}}}', false);

        return $core;
    }

    public function testGetSortByStrWithDefaultValues()
    {
        // copy from ../../../../inc/public/class.dc.template.php:getSortByStr()
        $default_alias = [
            'post' => [
                'title'     => 'post_title',
                'selected'  => 'post_selected',
                'author'    => 'user_id',
                'date'      => 'post_dt',
                'id'        => 'post_id',
                'comment'   => 'nb_comment',
                'trackback' => 'nb_trackback'
            ],
            'comment' => [
                'author' => 'comment_author',
                'date'   => 'comment_dt',
                'id'     => 'comment_id'
            ]
        ];

        $tpl = new \dcTemplate(sys_get_temp_dir(), '$tpl', $this->getCore());

        foreach ($default_alias as $table => $fields) {
            $this
                ->string($tpl->getSortByStr([], $table))
                ->isEqualTo($default_alias[$table]['date'] . ' desc');
            foreach ($fields as $field => $value) {
                $this
                    ->string($tpl->getSortByStr(['sortby' => $field], $table))
                    ->isEqualTo($value . ' desc');
                $this
                    ->string($tpl->getSortByStr(['sortby' => $field, 'order' => 'asc'], $table))
                    ->isEqualTo($value . ' asc');
            }
        }
    }

    public function testGetSortByStrWithNonExistingKeyAndExistingTable()
    {
        $tpl = new \dcTemplate(sys_get_temp_dir(), '$tpl', $this->getCore());
        $this
            ->string($tpl->getSortByStr(['sortby' => 'dummy_field'], 'post'))
            ->isEqualTo('post_dt desc')
            ->string($tpl->getSortByStr(['sortby' => 'dummy_field'], 'comment'))
            ->isEqualTo('comment_dt desc');
    }

    public function testGetSortByStrWithNewKeyAndExistingTable()
    {
        $new_alias                      = [];
        $new_alias['post']['category']  = 'cat_id';
        $new_alias['post']['format']    = 'post_format';
        $new_alias['comment']['filter'] = 'spam_filter';

        $core = $this->getCore();
        $core->addBehavior('templateCustomSortByAlias',
            function ($alias) use ($new_alias) {
                foreach ($new_alias as $table => $fields) {
                    foreach ($fields as $field => $value) {
                        $alias[$table][$field] = $value;
                    }
                }
            }
        );

        $tpl = new \dcTemplate(sys_get_temp_dir(), '$tpl', $core);
        foreach ($new_alias as $table => $fields) {
            foreach ($fields as $field => $value) {
                $this
                    ->string($tpl->getSortByStr(['sortby' => $field], $table))
                    ->isEqualTo($value . ' desc');
            }
        }
    }

    public function testGetSortByStrWithNewTable()
    {
        $new_alias                          = [];
        $new_alias['dummy_table']['field1'] = 'field1_id';
        $new_alias['dummy_table']['field2'] = 'field2_format';

        $core = $this->getCore();
        $core->addBehavior('templateCustomSortByAlias',
            function ($alias) use ($new_alias) {
                foreach ($new_alias as $table => $fields) {
                    foreach ($fields as $field => $value) {
                        $alias[$table][$field] = $value;
                    }
                }
            }
        );

        $tpl = new \dcTemplate(sys_get_temp_dir(), '$tpl', $core);
        foreach ($new_alias as $table => $fields) {
            foreach ($fields as $field => $value) {
                $this
                    ->string($tpl->getSortByStr(['sortby' => $field], $table))
                    ->isEqualTo($value . ' desc');
            }
        }
    }
}
