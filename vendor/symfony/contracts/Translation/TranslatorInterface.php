<?php
 namespace Symfony\Contracts\Translation; interface TranslatorInterface { public function trans($id, array $parameters = array(), $domain = null, $locale = null); } 