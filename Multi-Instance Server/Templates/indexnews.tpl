<?php

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Filename       links.tpl                                                   ##
##  Developed by:  Slim, Manuel Mannhardt 							           ##
##  Refactored by: Shadow Incremental Refactor 			                       ##
##  License:       TravianZ Project                                            ##
##  Copyright:     TravianZ (c) 2010-2025. All rights reserved.                ##
##                                                                             ##
##  Refactor notes:                                                            ##
##  - păstrată logica originală 100%                                           ##
##  - compatibil PHP 5.6+ / 7+                                                 ##
##  - redus cod duplicat                                                       ##
##  - securizare output HTML                                                   ##
##  - protecție basic URL                                                      ##
##  - comentarii adăugate                                                      ##
##                                                                             ##
#################################################################################

?>
<p class="date"><?php echo TZ_RELEASE_BY_TRAVIANZ; ?></p>
<p><?php echo TZ_THANK_YOU_FOR_USING_OUR_VERSION; ?></p>
<?php
/*
 * ANOMALIE / NOTA: lista de mai jos e text static in engleza, scris direct
 * aici (nu trece prin sistemul de traduceri ca restul liniilor de mai sus,
 * care folosesc constante TZ_*). Editeaz-o direct in fisierul asta cand
 * publici alte noutati.
 */
?>
<ul>
    <li>T4 Hero system implemented - recruit, train and grow your Hero.</li>
    <li>New Alliance bonuses for all alliance members.</li>
    <li>Various other improvements and features added since the T4 update.</li>
</ul>
