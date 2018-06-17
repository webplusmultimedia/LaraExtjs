<?php namespace Webplusm\LaraExtjs\Traits;


trait LaravextModel
{
    
    
    
    public static function baseQuery()
    {
        $query = self::select(['*']);
        return $query;
    }

    public function filterByKey()
    {
        return $this->baseQuery()->where($this->getKeyName(), $this->getKey());
    }
    
    /**
     * @return array
     */
    public function getrules()
    {
        if(self::$rules)
            return self::$rules;
        return [];
    }
}
