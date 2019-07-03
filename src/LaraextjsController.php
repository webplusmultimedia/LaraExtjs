<?php
    /**
     * Created by PhpStorm.
     * @package     LaraextjsController.php
     * @subpackage  Subpackage
     * @category    Category
     * @author      daniel
     * @link        http://webplusm.net
     * Date: 05/06/18 10:53
     */
    
    namespace Webplusm\LaraExtjs;
    
    
    use App\Http\Controllers\Controller;
    use Gate;
    use Illuminate\Http\Request;
    use Validator;
    
    class LaraextjsController extends Controller
    {
        protected $_model;
        protected $writerRootProperty;
        protected $readerRootProperty;
        protected $sortProperty;
        protected $_data = [];
        protected $_pagination = [];
        protected $_queryColumns = [];
        protected $_filter = [];
        protected $_queryValue;
        protected $_sorters = [];
        protected $_optionsParam = [];
        protected $_orderByIdParam;
        protected $_modelBaseQuery;
        protected $_rules = [];
        protected $messageTxt = "Ligne enregistrée avec succès";
        protected $errorMsg = [
            "notConnected"  => "Vous n'êtes pas Connecté",
            "notAuthorized" => "Vous n'êtes pas autorisé"
        ];
        protected $isAdminable = true;
        const PROFIL_MINIMAL = 4;
        
        
        protected $countTotalRows;
        private $request;
        private $start = 0;
        private $limit = 50;
        protected $isTotalRowsCount = true;
        
        
        public function __construct(Request $request)
        {
            
            
            $this->request = $request;
            $this->writerRootProperty = config('laraextjs.extjs.writer.rootProperty');
            $this->readerRootProperty = config('laraextjs.extjs.reader.rootProperty');
            $this->sortProperty = config('laraextjs.extjs.sorter.sortProperty');
            
            
            if (method_exists($this->_model, 'baseQuery')) {
                $this->_modelBaseQuery = $this->_model->baseQuery();
            } else {
                $this->_modelBaseQuery = $this->_model->query();
            }
            $params = $this->request->all();
            /*if ($this->request->get('disableCountResults') == 'true') {
                $this->setCountTotalRows(false);
            }*/
            
            $this->setCountTotalRows($this->isTotalRowsCount);
            
            
            if (isset($params[config('laravext.extjs.proxy.start_param')]) &&
                isset($params[config('laravext.extjs.proxy.limit_param')]) && is_null($this->countTotalRows)
            ) {
            }
            
            
            $this->decodeWriterRoot();
            $this->decodeSorters();
            $this->decodePaging();
            $this->decodeSearching();
            $this->decodeOptionsParam();
            $this->_rules = $this->_model->getRules();
            
            
        }
        
        protected function getModelBaseQuery()
        {
            if (method_exists($this->_model, 'baseQuery')) {
                $this->_modelBaseQuery = $this->_model->baseQuery();
            } else {
                $this->_modelBaseQuery = $this->_model->query();
            }
            
            return $this->_modelBaseQuery;
        }
        
        protected function success($options = [])
        {
            $options[config('laraextjs.extjs.reader.success_property')] = true;
            $options[config('laraextjs.extjs.reader.message_property')] = 'OK';
            
            return response()->json($options);
        }
        
        protected function failure($options = [], $status = null)
        {
            $options[config('laraextjs.extjs.reader.success_property')] = false;
            
            return response()->json($options, $status);
        }
        
        protected function setCountTotalRows($countTotalRows)
        {
            $this->countTotalRows = $countTotalRows;
        }
        
        
        protected function decodeWriterRoot()
        {
            $params = $this->request->all();
            if (isset($params[$this->writerRootProperty])) {
                $this->_data = json_decode($params[$this->writerRootProperty], true);
            }
        }
        
        protected function decodeSorters()
        {
            $params = $this->request->all();
            
            
            $sorters = [];
            if (isset($params[$this->sortProperty])) {
                $sortersArray = json_decode($params[$this->sortProperty], true);
                
                if (is_array($sortersArray) && count($sortersArray)) {
                    foreach ($sortersArray as $key => $sorter) {
                        $sorters[$key]['column'] = $sorter['property'];
                        $sorters[$key]['dir'] = $sorter['direction'];
                    }
                }
            }
            $this->_sorters = $sorters;
        }
        
        /**
         *
         */
        protected function decodePaging()
        {
            $params = $this->request->all();
            $pagination = [];
            if (isset($params['limit'])) {
                $pagination["limit"] = (int)$params[config('laraextjs.extjs.proxy.limit_param')];
                $pagination["start"] = (int)$params[config('laraextjs.extjs.proxy.start_param')];
                if (isset($params[config('laraextjs.extjs.proxy.page_param')])) {
                    $pagination["page"] = (int)$params[config('laraextjs.extjs.proxy.page_param')];
                }
            } else {
                $pagination["limit"] = $this->limit;
                $pagination["start"] = $this->start;
                if (isset($params[config('laraextjs.extjs.proxy.page_param')])) {
                    $pagination["page"] = (int)$params[config('laraextjs.extjs.proxy.page_param')];
                }
            }
            $this->_pagination = $pagination;
        }
        
        protected function decodeSearching()
        {
            $params = $this->request->all();
            
            if (isset($params[config('laraextjs.extjs.proxy.filter_param')])) {
                $this->_filter = json_decode($params[config('laraextjs.extjs.proxy.filter_param')], true);
                if (isset($params['ignoreFilter'])) {
                    if ($params['ignoreFilter'] == 'true') {
                        $this->_filter = [];
                    }
                }
            }
            if (isset($params['fields'])) {
                $this->_queryColumns = json_decode($params['fields'], true);
            }
            
            $this->_queryValue = isset($params['query']) ? $params['query'] : '';
        }
        
        
        protected function decodeOptionsParam()
        {
            $params = $this->request->all();
            
            if (isset($params['options'])) {
                $this->_optionsParam = json_decode($params['options'], true);
            }
            if ($this->request->get('orderById')) {
                $this->_orderByIdParam = $this->request->get('orderById');
            }
            
        }
        
        protected function applySearchToQuery($query)
        {
            if (count($this->_queryColumns) && !empty($this->_queryValue)) {
                $terms = explode(" ", $this->_queryValue);
                $stringTerms = '%';
                foreach ($terms as $term) {
                    $stringTerms .= $term . '%';
                }
                
                
                $queryColumns = $this->_queryColumns;
                $query->where(function ($q) use ($queryColumns, $stringTerms) {
                    for ($i = 0; $i < count($queryColumns); $i++) {
                        $column = $queryColumns[$i];
                        $q->orWhere($column, 'like', $stringTerms);
                    }
                });
            }
            
            return $query;
        }
        
        protected function applyFilterToQuery($query)
        {
            for ($i = 0; $i < count($this->_filter); $i++) {
                $filter = $this->_filter[$i];
                $field = $filter['property'];
                $value = $filter['value'];
                $propertyCastType = isset($filter['propertyCastType']) ? $filter['propertyCastType'] : null;
                
                $operator = isset($filter['comparison']) ? $filter['comparison'] : isset($filter['operator']) ? $filter['operator'] : null;
                $filterType = isset($filter['type']) ? $filter['type'] : null;
                $valeur = null;
                //var_dump($filterType, $operator, $value);
                switch ($filterType) {
                    case 'string' :
                        $query->where($field, "LIKE", "%{$value}%");
                        break;
                    case 'list' :
                        if (is_array($value) && count($value)) {
                            $query->whereIn($field, $value);
                        } else {
                            $query->where($field, "=", $value);
                        }
                        break;
                    case 'boolean' :
                        $query->where($field, "=", ($value));
                        break;
                    case 'numeric' :
                        switch ($operator) {
                            case 'eq' :
                                $query->where($field, "=", $value);
                                break;
                            case 'lt' :
                                $query->where($field, "<", $value);
                                break;
                            case 'gt' :
                                $query->where($field, ">", $value);
                                break;
                        }
                        break;
                    case 'date' :
                        switch ($operator) {
                            case 'eq' :
                                $query->where($field, "=", date('Y-m-d', strtotime($value)));
                                break;
                            case 'lt' :
                                $query->where($field, "<", date('Y-m-d', strtotime($value)));
                                break;
                            case 'gt' :
                                $query->where($field, ">", date('Y-m-d', strtotime($value)));
                                break;
                        }
                        break;
                    
                    default:
                        $useCastDate = [
                            'passado_ate_mes_atual', 'ate_hoje', 'hoje', 'ontem', 'amanha', 'mes_atual', 'ultimo_30_dias',
                            'ultimo_15_dias', 'ultimo_7_dias', 'proximo_30_dias', 'proximo_15_dias', 'proximo_7_dias', 'intervalo',
                            
                            'specify_date'
                        ];
                        if (in_array($operator, $useCastDate) && $value['castType'] === 'date') {
                            $field = DB::raw("cast($field as date)");
                        }
                        if ($propertyCastType) {
                            $field = DB::raw("cast($field as $propertyCastType)");
                        }
                        
                        switch ($operator) {
                            case 'having' :
                                
                                $query->havingRaw($field . " " . $value['operator'] . " " . DB::raw($value['value']));
                                
                                break;
    
                            // filtre par rapport à une relation : Has (contient)
                            case 'has' :
                                if(is_array($value)){
                                    $query->whereHas($field,function($q) use ($value){
                                        foreach ($value as $i => $v){
                                            $q->where(head($v),last($v));
                                        }
                                    });
                                }
                                else
                                    $query->has($field);
                                break;
    
                            // filtre par rapport à une relation : ou Has (ou contient)
                            case 'or_has' :
                                if(is_array($value)){
                                    $query->orWhereHas($field,function($q) use ($value){
                                        foreach ($value as $i => $v){
                                            $q->where(head($v),last($v));
                                        }
                                    });
                                }
                                else
                                    $query->orHas($field);
                                break;
    
                            // filtre par rapport à une relation :  doesnt Have (ne contient pas)
                            case 'not_has' :
                                if(is_array($value)){
                                    $query->whereDoesntHave($field,function($q) use ($value){
                                        foreach ($value as $i => $v){
                                            $q->where(head($v),last($v));
                                        }
                                    });
                                }
                                else
                                    $query->doesntHave($field);
                                break;
    
                            // filtre par rapport à une relation : ou doesnt Have (ou ne contient pas)
                            case 'or_not_has' :
                                if(is_array($value)){
                                    $query->orWhereDoesntHave($field,function($q) use ($value){
                                        foreach ($value as $i => $v){
                                            $q->where(head($v),last($v));
                                        }
                                    });
                                }
                                else
                                    $query->orDoesntHave($field);
                                break;
                            case 'or_sql' :
                                if (is_array($field)) {
                                    foreach ($field as $i => $v) {
                                        $query->orWhere($v, '!=', $value);
                                    }
                                }
                                break;
    
                            case 'and_or_sql' :
                                if (is_array($field)) {
                                    if (!is_array($value)) {
                                        $value = explode(',', $value);
                                    }
                                    foreach ($field as $i => $f) {
                
                                        if (is_array($value) && count($value)) {
                                            $query->where(function ($q) use ($f, $value) {
                                                foreach ($value as $v) {
                                                    switch ($v) {
                                                        case 'is_null':
                                                            $q->orWhereNull($f);
                                                            break;
                                                        case 'is_not_null':
                                                            $q->orWhereNotNull($f);
                                                            break;
                                                        default:
                                                            $q->orWhere($f, $v);
                                                            break;
                                
                                                    }
                            
                            
                                                }
                        
                                            });
                                        }
                                    }
                                }
                                break;
    
                            case 'like' :
                                if (!is_array($value) && !empty($value)) {
                                    $valeur = explode(';', $value);
                                    
                                }
                                if (is_array($valeur) && count($valeur) > 1) {
                                    $query->where(function ($q) use ($valeur, $field) {
                                        foreach ($valeur as $mot) {
                                            if (!empty($mot))
                                                $q->orWhere($field, 'like', "%" . $mot . "%");
                                        }
                                    });
                                    
                                    
                                } else
                                    $query->where($field, 'like', "%" . $value . "%");
                                break;
                            
                            case 'eq' :
                                if (is_double($value) || is_int($value))
                                    $query->where($field, "=", $value);
                                else
                                    $query->where($field, "=", date('Y-m-d', strtotime($value)));
                                break;
                            case 'lt' :
                                if (is_double($value) || is_int($value))
                                    $query->where($field, "<", $value);
                                else
                                    $query->where($field, "<", date('Y-m-d', strtotime($value)));
                                break;
                            case 'gt' :
                                if (is_double($value) || is_int($value))
                                    $query->where($field, ">", $value);
                                else
                                    $query->where($field, ">", date('Y-m-d', strtotime($value)));
                                break;
                            case '<=' :
                                if (is_double($value) || is_int($value))
                                    $query->where($field, "<=", $value);
                                else
                                    $query->where($field, "<=", date('Y-m-d', strtotime($value)));
                                break;
                            case '>=' :
                                if (is_double($value) || is_int($value))
                                    $query->where($field, ">=", $value);
                                else
                                    $query->where($field, ">=", date('Y-m-d', strtotime($value)));
                                break;
                            
                            case '==' :
                                $query->where($field, "=", ($value));
                                break;
                            
                            case 'in' :
                                if (!is_array($value) && !empty($value)) {
                                    $value = explode(',', $value);
                                }
                                if (is_array($value) && count($value)) {
                                    $query->whereIn($field, $value);
                                }
                                break;
                            case 'and_or' :
                                
                                if (!is_array($value)) {
                                    $value = explode(',', $value);
                                }
                                if (is_array($value) && count($value)) {
                                    $query->where(function ($q) use ($field, $value) {
                                        foreach ($value as $v) {
                                            switch ($v) {
                                                case 'is_null':
                                                    $q->orWhereNull($field);
                                                    break;
                                                case 'is_not_null':
                                                    $q->orWhereNotNull($field);
                                                    break;
                                                default:
                                                    $q->orWhere($field, $v);
                                                    break;
                                                
                                            }
                                            
                                            
                                        }
                                        
                                    });
                                }
                                break;
                            case 'not_in' :
                                if (!is_array($value)) {
                                    $value = explode(',', $value);
                                }
                                if (is_array($value) && count($value)) {
                                    $query->whereNotIn($field, $value);
                                }
                                break;
                            case 'between' :
                                if (!empty($value)) {
                                    $query->whereBetween($field, $value);
                                }
                                break;
                            case 'not_between' :
                                $query->whereNotBetween($field, $value);
                                break;
                            
                            case 'not_null' :
                                $query->whereNotNull($field);
                                break;
                            
                            case 'with_trashed' :
                                $query->withTrashed();
                                break;
                            
                            case 'only_trashed' :
                                $query->onlyTrashed();
                                break;
                            
                            case 'null' :
                                $query->whereNull($field);
                                break;
                            
                            case 'today' :
                                
                                $query->where($field, Carbon::today()->toDateString());
                                break;
                            case 'all_until_today' :
                                
                                $query->where($field, '<=', Carbon::today()->toDateString());
                                break;
                            case 'all_until_current_month' :
                                
                                $query->where($field, '<=', Carbon::now()->lastOfMonth()->toDateString());
                                break;
                            
                            case 'yesterday' :
                                $query->where($field, Carbon::yesterday()->toDateString());
                                break;
                            
                            case 'tomorrow' :
                                $query->where($field, Carbon::tomorrow()->toDateString());
                                break;
                            
                            case 'current_month' :
                                $start = Carbon::now()->firstOfMonth();
                                $end = Carbon::now()->lastOfMonth();
                                $query->whereBetween($field, [$start->toDateString(), $end->toDateString()]);
                                break;
                            
                            case 'last_30_days' :
                                $start = Carbon::today()->subDays(30);
                                $end = Carbon::today();
                                $query->whereBetween($field, [$start->toDateString(), $end->toDateString()]);
                                break;
                            
                            case 'next_30_days' :
                                $start = Carbon::today();
                                $end = Carbon::today()->addDays(30);
                                $query->whereBetween($field, [$start->toDateString(), $end->toDateString()]);
                                break;
                            case 'next_15_days' :
                                $start = Carbon::today();
                                $end = Carbon::today()->addDays(15);
                                $query->whereBetween($field, [$start->toDateString(), $end->toDateString()]);
                                break;
                            case 'next_7_days' :
                                $start = Carbon::today();
                                $end = Carbon::today()->addDays(7);
                                $query->whereBetween($field, [$start->toDateString(), $end->toDateString()]);
                                break;
                            case 'specify_date' :
                                $start = Carbon::parse($value['startDate']);
                                $end = Carbon::parse($value['endDate']);
                                $query->whereBetween($field, [$start->toDateString(), $end->toDateString()]);
                                break;
                            
                            case 'specify_months' :
                                $query->where(function ($query) use ($field, $value) {
                                    if (!empty($value['months'])) {
                                        foreach ($value['months'] as $k => $v) {
                                            $query->orWhere(DB::raw("EXTRACT(MONTH FROM $field)"), $v);
                                        }
                                    }
                                });
                                $query->where(DB::raw("EXTRACT(YEAR FROM $field)"), $value['year']);
                                break;
                            
                            default:
                                if ($field) {
                                    $query->where($field, isset($filter['operator']) ? $filter['operator'] : "=", $value);
                                }
                                break;
                        }
                        break;
                }
            }
        }
        
        protected function applyLimitToQuery($query)
        {
            if (count($this->_pagination)) {
                $query->take($this->_pagination["limit"]);
                $query->skip($this->_pagination["start"] ? $this->_pagination["start"] : 0);
            }
            
            return $query;
        }
        
        protected function applySorterToQuery($query)
        {
            if (isset($this->_orderByIdParam)) {
                $query->orderBy(DB::raw($this->_getModel()->getKeyName() . '=' . $this->_orderByIdParam), 'DESC');
                $query->orderBy($this->_getModel()->getKeyName(), 'ASC');
            }
            if (is_array($this->_sorters)) {
                for ($i = 0; $i < count($this->_sorters); $i++) {
                    $dir = !empty($this->_sorters[$i]['dir']) ? $this->_sorters[$i]['dir'] : "ASC";
                    $query->orderBy($this->_sorters[$i]['column'], $dir);
                }
            }
            
            return $query;
        }
        
        
        protected function read()
        {
            if ($this->isAdminable) {
                if (!Gate::allows('read', $this->_model)) {
                    return $this->failure(
                        [
                            config('laravext.extjs.reader.message_property') => $this->errorMsg['notAuthorized'],
                            "data"                                           => []
                        ], 200
                    );
                }
            }
            $this->messageTxt = "Ok";
            
            return $this->index();
            
        }
        
        protected function index()
        {
            $response = [];
            
            $query = $this->_modelBaseQuery;
            $this->applyFilterToQuery($query);
            $this->applySearchToQuery($query);
            
            //dd($query);
            
            if ($this->countTotalRows) {
                $cloneQuery = clone $query;
                $count = $query->count($this->_model->getTable() . '.' . $this->_model->getKeyName());
                $this->applySorterToQuery($cloneQuery);
                $data = $this->applyLimitToQuery($cloneQuery)->get();
                $response[config('laraextjs.extjs.reader.total_property')] = $count;
            } else {
                $this->applySorterToQuery($query);
                $data = $this->applyLimitToQuery($query)->get();
            }
            
            $response[$this->readerRootProperty] = (is_array($data) ? $data : $data->toArray());
            
            return $this->success($response);
        }
        
        public function store()
        {
            if ($this->isAdminable) {
                if (!Gate::allows('post', $this->_model)) {
                    return $this->failure([config('laravext.extjs.reader.message_property') => $this->errorMsg['notAuthorized']], 200);
                }
            }
            
            return $this->postCreate();
        }
        
        public function create()
        {
            if ($this->isAdminable) {
                if (!Gate::allows('post', $this->_model)) {
                    return $this->failure([config('laravext.extjs.reader.message_property') => $this->errorMsg['notAuthorized']], 200);
                }
            }
            
            return $this->postCreate();
        }
        
        public function postCreate()
        {
            $validator = Validator::make($this->_data, $this->_rules);
            if ($validator->fails()) {
                return $this->failure(
                    [
                        config('laraextjs.extjs.reader.message_property') => ['msg'    => "Erreur de création",
                                                                              "errors" => $validator->getMessageBag()->all()]
                    ],
                    200
                );
            } else {
                $this->_model->fill($this->_data);
                $this->_model->save();
                $rootData = method_exists($this->_model, 'filterByKey') ? $this->_model->filterByKey()->first() : $this->_model->toArray();
                if (isset($this->_data[$this->_model->getKeyName()])) {
                    $rootData[config('laraextjs.extjs.client_id_property')] = $this->_data[$this->_model->getKeyName()];
                }
                
                return $this->success([$this->readerRootProperty => $rootData]);
            }
            
        }
        
        
        public function update($id)
        {
            if ($this->isAdminable) {
                if (!Gate::allows('update', $this->_model)) {
                    return $this->failure([config('laravext.extjs.reader.message_property') => $this->errorMsg['notAuthorized']], 200);
                }
            }
            
            return $this->putUpdate($id);
        }
        
        public function putUpdate($id)
        {
            if (count($this->_data) === 0) {
                return $this->failure([
                    'message' => 'Pas de données à mettre à jour',
                    'key'     => $id
                ], 200);
            }
            $model = $this->_model->find($id);
            
            if (is_null($model)) {
                return $this->failure([
                    'message' => 'Pas de ligne à mettre à jour',
                    'key'     => $id
                ], 200);
            }
            $validator = Validator::make($this->_data, $this->_rules);
            if ($validator->fails()) {
                return $this->failure(
                    [
                        config('laraextjs.extjs.reader.message_property') => ['msg'    => "Erreur de modification",
                                                                              "errors" => $validator->getMessageBag()->all()]
                    ],
                    200
                );
            } else {
                $model->fill($this->_data);
                $saved = $model->save();
                
                if (!$saved) {
                    return $this->failure([
                        config('laravext.extjs.reader.message_property') => 'Erreur de modification <br> un problème est survenu lors de la sauvegarde',
                        'key'                                            => $id
                    ], 200);
                }
                
                return $this->success([
                    $this->writerRootProperty => method_exists($model, 'filterByKey') ? $model->filterByKey()->first() : $model->toArray()
                ]);
            }
            
        }
        
        protected function find($id)
        {
            $record = $this->getModelBaseQuery()->where($this->_model->getKeyName(), $id)->first();
            
            return $this->success([
                $this->readerRootProperty => $record ? $record->toArray() : []
            ]);
        }
        
        protected function show($id)
        {
            if ($this->isAdminable) {
                if (!Gate::allows('read', $this->_model)) {
                    return $this->failure(
                        [
                            config('laravext.extjs.reader.message_property') => $this->errorMsg['notAuthorized'],
                            "data"                                           => []
                        ], 200
                    );
                }
            }
            $this->messageTxt = "Ok";
            
            return $this->find($id);
        }
        
        protected function destroy($id)
        {
            $this->messageTxt = "Ligne supprimée avec succès";
            if ($this->isAdminable) {
                if (!Gate::allows('delete', $this->_model)) {
                    return $this->failure([config('laravext.extjs.reader.message_property') => $this->errorMsg['notAuthorized']], 200);
                }
            }
            
            $model = $this->_model->find($id);
            if (is_null($model)) {
                
                return $this->failure([
                    config('laravext.extjs.reader.message_property') => "Erreur de Supression <br>Aucune ligne n'a été trouver",
                    'key'                                            => $id
                ], 200);
            }
            $deleted = $model->delete();
            if (!$deleted) {
                return $this->failure([
                    config('laravext.extjs.reader.message_property') => 'Erreur de Supression <br> Un problème est survenu lors de la suppression',
                    'key'                                            => $id
                ], 200);
            }
            
            return $this->success();
        }
        
        protected function batchStore(array $collection)
        {
            $responseData = [];
            foreach ($collection as $key => $data) {
                $class = get_class($this->_model);
                $model = new $class($data);
                $idProperty = $model->getKeyName();
                $model->save();
                $result = $model->filterByKey()->first()->toArray();
                $result[config('laraextjs.extjs.client_id_property')] = $data[$idProperty];
                $responseData[] = $result;
                
            }
            
            return $this->success([
                $this->readerRootProperty => $responseData
            ]);
        }
        
        protected function batchUpdate(array $collection)
        {
            $responseData = [];
            foreach ($collection as $key => $data) {
                $hasIdProperty = isset($data[$this->_model->getKeyName()]);
                $idProperty = $this->_model->getKeyName();
                if ($hasIdProperty) {
                    $modelUpdated = $this->_model->find($data[$idProperty]);
                    if ($modelUpdated) {
                        $modelUpdated->fill($data);
                        $modelUpdated->save();
                        $result = $modelUpdated->baseQuery()->where($idProperty, $data[$idProperty])->first()->toArray();
                        $result[config('laraextjs.extjs.client_id_property')] = $data[$idProperty];
                        $responseData[] = $result;
                    }
                    
                }
            }
            
            return $this->success([
                $this->readerRootProperty => $responseData
            ]);
        }
        
        protected function batchDestroy(array $collection)
        {
            foreach ($collection as $key => $data) {
                $hasIdProperty = isset($data[$this->_model->getKeyName()]);
                $idProperty = $this->_model->getKeyName();
                if ($hasIdProperty) {
                    $model = $this->_model->find($data[$idProperty]);
                    if ($model) {
                        $model->delete();
                    }
                }
            }
            
            return $this->success();
        }
        
        protected function isBatchOperation()
        {
            foreach ($this->_data as $value) {
                if (is_array($value)) {
                    return true;
                }
            }
            
            return false;
        }
        
        protected function isAuthorize($verbe)
        {
            $verbeUser = $verbe . "lvl";
            $verbeModel = $verbe . "Level";
            if (empty($this->request->user())) {
                $this->messageTxt = "Vous n'êtes pas Connecté";
                
                return false;
            } elseif ($this->request->user()->{$verbeUser} >= $this->_model->{$verbeModel} && \Auth::user()->profil > self::PROFIL_MINIMAL) {
                return true;
            }
            $this->messageTxt = "Vous n'êtes pas autorisé";
            
            return false;
        }
    }
