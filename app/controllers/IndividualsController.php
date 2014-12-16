<?php
/*
* TreeChecker: Error recognition for genealogical trees
*
* Copyright (C) 2014 Digital Humanities Lab, Faculty of Humanities, Universiteit Utrecht
* Corry Gellatly <corry.gellatly@gmail.com>
* Martijn van der Klis <M.H.vanderKlis@uu.nl>
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/
class IndividualsController extends BaseController
{
protected $layout = "layouts.main";
/**
* Show a list of all the GedcomIndividuals.
*/
public function getIndex()
{
$source = 'individuals/data';
$title = Lang::get('gedcom/individuals/title.individuals_management');
$this->layout->content = View::make('gedcom/individuals/index', compact('source', 'title'));
}
/**
* Shows the details for a GedcomIndividual
* @param int $id
*/
public function getShow($id)
{
$individual = GedcomIndividual::findOrFail($id);
$this->layout->content = View::make('gedcom/individuals/detail', compact('individual'));
}
/**
* Show a list of all the GedcomIndividuals formatted for Datatables.
* @return Datatables JSON
*/
public function getData()
{
$individuals = GedcomIndividual::leftJoin('gedcoms', 'gedcoms.id', '=', 'individuals.gedcom_id')
->select(array('gedcoms.file_name AS gedc', 'gedcoms.id AS gedcom_id',
'gedcom_key', 'first_name', 'last_name', 'sex', 'individuals.id'));
return Datatables::of($individuals)
->edit_column('gedc', '{{ HTML::link("gedcoms/show/" . $gedcom_id, $gedc) }}')
->edit_column('gedcom_key', '{{ HTML::link("individuals/show/" . $id, $gedcom_key) }}')
->remove_column('id')
->remove_column('gedcom_id')
->make();
}
/**
* Show a list of all the GedcomEvents for the given GedcomIndividual formatted for Datatables.
* @return Datatables JSON
*/
public function getEvents($id)
{
$events = GedcomEvent::select(array('event', 'date', 'place'))->where('indi_id', $id);
return Datatables::of($events)->make();
}
/**
* Show a list of all the GedcomErrors for the given GedcomIndividual formatted for Datatables.
* @return Datatables JSON
*/
public function getErrors($id)
{
$events = GedcomError::select(array('severity', 'message'))->where('indi_id', $id);
return Datatables::of($events)->make();
}
/**
* Creates the ancestor family tree in JSON format for a GedcomIndividual
* @param int $id
* @return JSON
*/
public function getAncestors($id)
{
$individual = GedcomIndividual::findOrFail($id);
$tree_part = $this->toAncestorTree($individual, 2); // 2 levels deep (TODO: configurable?)
return Response::json($tree_part);
}
/**
* Creates the descendant family tree in JSON format for a GedcomIndividual
* @param int $id
* @return JSON
*/
public function getDescendants($id)
{
$individual = GedcomIndividual::findOrFail($id);
$tree_part = $this->toDescendantTree($individual, 2); // 2 levels deep (TODO: configurable?)
return Response::json($tree_part);
}
/**
* Turns a GedcomIndividual into an ancestor tree representation
* @param GedcomIndividual $individual
* @param int $depth
* @return array
*/
private function toAncestorTree($individual, $depth = 0)
{
// Create the individual array
$ind = $this->toArray($individual);
// Collect the parents (and decrease depth)
$parents = array();
if ($depth > 0 && $individual->father() && $individual->father)
{
array_push($parents, $this->toAncestorTree($individual->father, $depth - 1));
}
if ($depth > 0 && $individual->mother() && $individual->mother)
{
array_push($parents, $this->toAncestorTree($individual->mother, $depth - 1));
}
$ind['parents'] = $parents;
return $ind;
}
/**
* Turns a GedcomIndividual into a descendant tree representation
* @param GedcomIndividual $individual
* @param int $depth
* @return array
*/
private function toDescendantTree($individual, $depth = 0)
{
// Create the individual array
$ind = $this->toArray($individual);
// Collect the spouses and the collective children
$spouses = array();
$families = $individual->familiesAsWife->merge($individual->familiesAsHusband);
foreach ($families as $family)
{
// Add spouse (if exists)
$s = $family->spouse($individual);
$spouse = $s ? $this->toArray($s) : $this->toAnonymousArray($individual, TRUE);
// Add children
$children = array();
if ($depth > 0)
{
foreach ($family->children as $child)
{
array_push($children, $this->toDescendantTree($child, $depth - 1));
}
}
// Add children to spouse
$spouse['children'] = $children;
array_push($spouses, $spouse);
}
$ind['children'] = $spouses;
return $ind;
}
/**
* Returns the array representation of a GedcomIndividual
* @param GedcomIndividual $individual
* @return array
*/
private function toArray($individual)
{
return array(
'name' => $individual->first_name . ' ' . $individual->last_name,
'color' => $individual->sex === 'm' ? 'blue' : ($individual->sex === 'f' ? 'red' : 'grey'),
'born' => $individual->birth() ? $individual->birth()->date : '?',
'died' => $individual->death() ? $individual->death()->date : '?',
'location' => $individual->birth() ? $individual->birth()->location : '?',
'url' => URL::to('individuals/show/' . $individual->id),
);
}
/**
* Returns the array representation of an unknown GedcomIndividual
* @param GedcomIndividual $individual
* @return array
*/
private function toAnonymousArray($individual)
{
return array(
'name' => Lang::get('common/common.nomen_nescio'),
'color' => $individual->sex === 'm' ? 'red' : ($individual->sex === 'f' ? 'blue' : 'grey'),
'born' => '?',
'died' => '?',
'location' => '?',
'url' => URL::to('individuals/show/' . $individual->id),
);
}
}