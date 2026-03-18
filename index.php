<?php

$name = getenv('NAME', true) ?: 'World';
echo sprintf('Hello IMS BASEL %s!', $name);