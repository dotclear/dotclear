<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Antispam, a plugin for Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

$core->addBehavior('publicBeforeCommentCreate',array('dcAntispam','isSpam'));
$core->addBehavior('publicBeforeTrackbackCreate',array('dcAntispam','isSpam'));
$core->addBehavior('publicBeforeDocument',array('dcAntispam','purgeOldSpam'));
?>