<?php

namespace dokuwiki\plugin\aichat\Model\VoyageAI;

use dokuwiki\plugin\aichat\Model\AbstractModel;
use dokuwiki\plugin\aichat\Model\EmbeddingInterface;
use dokuwiki\plugin\aichat\Model\Generic\AbstractGenericModel;
use dokuwiki\plugin\aichat\Model\Generic\EmbeddingModel as GenericEmbeddingModel;

class EmbeddingModel extends GenericEmbeddingModel
{
    protected $apiurl = 'https://api.voyageai.com/v1/';
}
