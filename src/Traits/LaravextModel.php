<?php namespace Webplusm\LaraExtjs\Traits;


trait LaravextModel
{
    
    
    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function baseQuery()
    {
        $query = self::select(['*']);
        return $query;
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
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
