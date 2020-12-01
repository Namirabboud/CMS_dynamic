<?php

namespace Eve\Dynamic\Controllers;

use DataTables;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Traits\FormTrait;
use App\Http\Traits\FileTrait;
use Eve\Dynamic\Models\TableField;
use App\Http\Controllers\Controller;

class GeneralController extends Controller
{
    use FormTrait;
    use FileTrait;

    public function index(Request $request)
    {

        $query_string = http_build_query($request->only('model_name','row_id','filter_field','filter_value'));


        $model_name = request('model_name');

        $model = app("App\Models\\$model_name");

        $fields = TableField::where('model_name', $model_name)->orderBy('order', 'asc')->where('is_visible', true)->get();

        $tableHeaders = [];
        $rowColumns = [];
        $columns = [];


        //first add the
        $columns[] = ['data' => 'id', 'name' => 'id'];
        $tableHeaders[] = 'ID';





        $data = $model::when(request('row_id'),function($q){
            $q->where('id', request('row_id'));
        })->when(request('filter_field'), function($q){
            return $q->where(request('filter_field'), request('filter_value'));
        });

        $dt = DataTables::of($data);

        foreach($fields as $field){

            $dt = $dt->addColumn($field->field_name, function($row) use ($field){

                $f = $row->{$field->field_name};
                $type = $field->field_type;



                if($type == 'checkbox')
                    return $f ? 'true' : 'false';

                else if($type == 'image')
                    return $this->drawImage($f);

                else if($type == 'foreign'){

                    $foreign_model_name = $field->foreign_table;
                    $foreignModel = app("App\Models\\$foreign_model_name");
                    $foreignInstance = $foreignModel->find($f);

                    return $this->drawLink(route('admin.general.index').'?model_name='.$field->foreign_table.'&row_id='.$f, $foreignInstance->{$field->foreign_field});

                }

                else return $f;

            });

            $field_name = $field->field_name;


            $rowColumns[] = $field_name;
            $columns[] = ['data' => $field_name, 'name' => $field_name];
            $tableHeaders[] = slugToString($field->field_name);
        }


        //add the relatedTables if exists
        $related_columns = TableField::where('foreign_table', $model_name)->get();

        foreach($related_columns as $column){

            $related_model = $column->model_name;


            $dt = $dt->addColumn($related_model, function($row) use ($query_string, $related_model,$column){

                $filter = [
                    'filter_field' => $column->field_name,
                    'filter_value' => $row->id,
                ];


                //construct the link to the relation table
                $link = route('admin.general.index').'?model_name='.$related_model.'&'.http_build_query($filter);


                return $this->drawLink($link, $related_model);
            });

            $rowColumns[] = $related_model;
            $columns[] = ['data' => $related_model, 'name' => $related_model];
            $tableHeaders[] = slugToString($column->field_name);
        }



        $dt = $dt->addColumn('action', function($row) use ($query_string){
            return
                "<a class='edit-link' href='" . route('admin.general.edit', $row->id) .'?'.$query_string."'>".
                "<i class='fa fa-edit'></i>".
                "<a data-toggle='modal' class='delete-link' href='#deleteModal' id='" .route('admin.general.destroy', $row->id).'?'.$query_string. "'>".
                "<i class='fa fa-trash' style='color: red;' aria-hidden='true'></i>";
        });



        $rowColumns[] = 'action';
        $columns[] = ['data' => 'action', 'name' => 'action', 'searchable' => false, 'sortable' => false];
        $tableHeaders[] = 'Action';

        $dt = $dt->rawColumns($rowColumns);
        $dt = $dt->make(true);


        if($request->ajax()){
            return $dt;
        }

        //dd($columns,$rowColumns,$tableHeaders);

        return view('components::table_ajax')->with([
            'layout'    => 'layouts::cms',
            'pageTitle'	=> slugToString($model_name),
            'table_title' => '',
            'table_btns' => "<a href='" . route('admin.general.create').'?'.$query_string."' class='btn btn-primary'>Add ". slugToString($model_name) ."</a>",
            'slug'		=> slugToString($model_name),
            'headers'	=> $tableHeaders,
            'action' => route('admin.general.index').'?'.$query_string,
            'columns' => json_encode($columns),
        ]);
    }

    public function create(Request $request)
    {
        $query_string = http_build_query($request->only('model_name'));

        $model_name = request('model_name');

        $fields = TableField::where('model_name', $model_name)->orderBy('order', 'asc')->get();


        $form_fields = [];

        foreach($fields as $field){

            if($field->field_type == 'multiple-file-upload'){
                $query_string .= '&field_name='.$field->field_name;
                $form_fields[] = $this->drawHtml('multiple-file-upload', slugToString($field->field_name), $field->field_name, null, ['add' => route('admin.generalImage.upload').'?'.$query_string, 'delete' => route('admin.generalImage.delete').'?'.$query_string, 'default' => null],'', $field->class);

            }elseif($field->field_type == 'foreign'){

                //get the select-box data from foreign table
                $option_name = $field->foreign_field;
                $foreign_table = $field->foreign_table;
                $foreign = app("App\Models\\$foreign_table");
                $select_box_options = $foreign::all()->pluck($option_name,'id')->toArray();


                $form_fields[] = $this->drawHtml('select-box', slugToString($field->field_name), $field->field_name, $request->old($field->field_name) , $select_box_options, '', $field->class.' '.($field->mandatory ? ' required' : ''));

            }else{
                $form_fields[] = $this->drawHtml($field->field_type, slugToString($field->field_name), $field->field_name, $request->old($field->field_name) , null, '', $field->class.' '.($field->mandatory ? ' required' : ''));
            }
        }

        return view('components::form')->with([
            'layout'         => 'layouts::cms',
            'pageTitle'		=> 'Add '.$model_name,
            'method'		=> 'post',
            'form_action'	=> route('admin.general.store').'?'.$query_string,

            'boxes' => [
                [
                    'wrapper-class' => 'col-md-12',
                    'class' => 'box-default',
                    'box-header' => 'Info',
                    'form_fields' => $form_fields,
                ],
            ]
        ]);
    }

    public function store(Request $request)
    {

        $query_string = http_build_query($request->only('model_name'));

        $model_name = request('model_name');

        $model = app("App\Models\\$model_name");
        $instance = new $model;
        $table_name = $instance->getTable();

        $fields = TableField::where('model_name', $model_name)->where('field_type', '!=', 'multiple-file-upload')->orderBy('order', 'asc')->get();

        $validations = [];

        foreach($fields as $field){
            $rules = [];

            if($field->mandatory)
                $rules[] = 'required';


            if($field->field_type == 'image'){
                $rules[] = 'image';
                $rules[] = 'max:700';
            }

            $validations[$field->field_name] = implode('|', $rules);
        }

        $this->validate($request, $validations);

        $model = new $model;

        foreach($fields as $field){

            $name = $field->field_name;
            $type = $field->field_type;

            if($type == 'image' && request($name)){
                $this->removeFile($model->image);
                $model->{$name} = $this->moveFile(request($name),'images/'.$model_name);
            }

            else{
                $model->{$name} = request($name);
            }
        }

        $model->save();


        //check if multiple images exists
        $multple_image_field = TableField::where('model_name', $model_name)->where('field_type','multiple-file-upload')->get();

        foreach($multple_image_field as $field){
            //dd(request($name), $r->{$name});

            $name = $field->field_name;
            $type = $field->field_type;

            $new_images = request($name) ? json_decode(request($name)) : [];


            if($model->{$name}){

                //remove deleted images
                foreach($model->{$name} as $image){
                    $i = explode('/', $image->src);
                    $image_name = end($i);

                    if(!in_array($image_name, $new_images)){
                        $image->delete();
                        $this->removeFile($image->src);
                    }
                }
            }

            //add new images if exists
            if($new_images){

                foreach($new_images as $image){
                    if(!$model->$name()->where('src', $image)->first()){
                        $model->$name()->create([
                            str_singular($table_name).'_id' => $model->id,
                            'src' => $image,
                        ]);
                    }
                }
            }
        }

        $redirect_url = route('admin.general.index').'?'.$query_string;

        return redirect($redirect_url)->with('message', 'Row Added');
    }

    public function edit($id,Request $request)
    {

        $query_string = http_build_query($request->only('model_name'));

        $model_name = request('model_name');

        $model = app("App\Models\\$model_name");

        $r = $model::find($id);

        $fields = TableField::where('model_name', $model_name)->orderBy('order', 'asc')->get();


        $form_fields = [];

        foreach($fields as $field){

            if($field->field_type == 'multiple-file-upload'){

                $query_string .= '&field_name='.$field->field_name;
                $form_fields[] = $this->drawHtml('multiple-file-upload', slugToString($field->field_name), $field->field_name,  $r->{'my'.ucfirst($field->field_name)}, ['add' => route('admin.generalImage.upload').'?'.$query_string, 'delete' => route('admin.generalImage.deleteEdit').'?'.$query_string, 'default' => route('admin.generalImage.get', $r->id).'?'.$query_string],'', $field->class);

            }elseif($field->field_type == 'foreign'){

                //get the select-box data from foreign table
                $option_name = $field->foreign_field;
                $foreign_table = $field->foreign_table;
                $foreign = app("App\Models\\$foreign_table");
                $select_box_options = $foreign::all()->pluck($option_name,'id')->toArray();


                $form_fields[] = $this->drawHtml('select-box', slugToString($field->field_name), $field->field_name, $r->{$field->field_name} , $select_box_options, '', $field->class.' '.($field->mandatory ? ' required' : ''));

            }else{
                $form_fields[] = $this->drawHtml($field->field_type, slugToString($field->field_name), $field->field_name, $r->{$field->field_name} , null, '', $field->class.' '.($field->mandatory ? ' required' : ''));
            }
        }


        return view('components::form')->with([
            'layout'         => 'layouts::cms',
            'pageTitle'		=> 'Add '.$model_name,
            'method'		=> 'update',
            'form_action'	=> route('admin.general.update', $id).'?'.$query_string,

            'boxes' => [
                [
                    'wrapper-class' => 'col-md-12',
                    'class' => 'box-default',
                    'box-header' => 'Info',
                    'form_fields' => $form_fields,
                ],
            ]
        ]);
    }

    public function update($id, Request $request)
    {
        //dd($request->all());

        $query_string = http_build_query($request->only('model_name'));

        $model_name = request('model_name');

        $model = app("App\Models\\$model_name");
        $instance = new $model;
        $table_name = $instance->getTable();

        $r = $model::find($id);

        $fields = TableField::where('model_name', $model_name)->orderBy('order', 'asc')->get();


        $validations = [];

        foreach($fields as $field){
            $rules = [];

            if($field->mandatory)
                $rules[] = 'required';


            if($field->field_type == 'image'){
                $rules[] = 'nullable';
                $rules[] = 'image';
                $rules[] = 'max:700';
            }

            $validations[$field->field_name] = implode('|', $rules);
        }


        $this->validate($request, $validations);

        foreach($fields as $field){

            $name = $field->field_name;
            $type = $field->field_type;

            if($type == 'image' && request($name)){
                $this->removeFile($model->image);
                $r->{$name} = $this->moveFile(request($name),'images/'.$model_name);
            }

            else if($type == 'multiple-file-upload'){

                //dd(request($name), $r->{$name});

                $new_images = request($name) ? json_decode(request($name)) : [];


                if($r->{$name}){

                    //remove deleted images
                    foreach($r->{$name} as $image){
                        $i = explode('/', $image->src);
                        $image_name = end($i);

                        if(!in_array($image_name, $new_images)){
                            $image->delete();
                            $this->removeFile($image->src);
                        }
                    }
                }

                //add new images if exists
                if($new_images){

                    foreach($new_images as $image){
                        if(!$r->$name()->where('src', $image)->first()){
                            $r->$name()->create([
                                str_singular($table_name).'_id' => $r->id,
                                'src' => $image,
                            ]);
                        }
                    }
                }
            }

            else{
                $r->{$name} = request($name);
            }
        }

        $r->save();

        $redirect_url = route('admin.general.index').'?'.$query_string;

        return redirect($redirect_url)->with('message', 'Row Updated');
    }

    public function destroy($id, Request $request)
    {
        $query_string = http_build_query($request->only('model_name'));

        $model_name = request('model_name');

        $model = app("App\Models\\$model_name");

        $r = $model::find($id);

        $r->delete();

        return back()->with('message', 'Row deleted');
    }

    public function imageUpload(Request $request)
    {


        $this->validate($request, [
            'myfile' => 'required|image|max:700',
        ]);


        $model_name = request('model_name');

        $image = $request->file('myfile');

        return $this->moveFile($image, 'images/'.$model_name);
    }

    public function deleteUpload(Request $request)
    {
        $name = request('name');

        $model_name = request('model_name');
        $this->removeFile('images/'.$model_name.'/'.$name);

        return $name;
    }

    public function deleteUploadEdit(Request $request){
        $name = request('name');

        if(is_array($name))
            return $name[0];

        return $name;
    }

    public function getDefault($id)
    {
        $model_name = request('model_name');
        $field_name = request('field_name');

        $model = app("App\Models\\$model_name");

        $p = $model::find($id);

        $images = $p->{$field_name};

        $my_images = [];

        foreach($images as $image){

            $name = explode('/', $image->src);
            $name = end($name);

            $my_images[] = [
                'path' => url($image->src),
                'name' => $name,
                'size' => 'size',
            ];
        }

        return json_encode($my_images);
    }
}

