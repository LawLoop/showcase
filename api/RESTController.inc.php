<?php

class RESTController
{
    protected $__class = "";

    public function __construct($class)
    {
        $this->__class = $class;
    }

    // get em all
    public function all($request, $response, $order = [])
    {
        $class = $this->__class;
        $response->json($class::all($order));
    }
    // get one
    public function get($request, $response)
    {
        $class = $this->__class;
        $object = $class::find($request->id);
        $response->json($object);
    }

    // update
    public function update($request, $response)
    {
        global $__PUT;
        $class = $this->__class;
        $object = $class::find($request->id);
        if (!empty($object))
        {
            $object->takeValues($__PUT);
            $object->save();
            $response->json($object);
        }
        else
        {
            $response->json(['error' => "{$class} not found"]);
        }
    }

    // create
    public function create($request, $response)
    {
        $class = $this->__class;
        $object = new $class($request->paramsPost());
        try
        {
            $object->save();
            $response->json($object);
        }
        catch(\Exception $ex)
        {
            $response->json(['error' => $ex->getMessage()]);

        }
    }

    public function delete($request, $response)
    {
        // we don't do that
    }
}