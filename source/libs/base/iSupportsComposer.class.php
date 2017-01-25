<?php

interface iSupportsComposer
{
    public function getComposerAutoloadFilename();

    public function shouldAutoloadComposer();
}