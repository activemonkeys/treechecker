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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class FamiliesController extends BaseController
{

    protected $layout = "layouts.main";

    /**
     * Show a list of all the GedcomFamilies.
     */
    public function getIndex()
    {
        $source = 'families/data';
        $title = Lang::get('gedcom/families/title.families_management');
        $this->layout->content = View::make('gedcom/families/index', compact('source', 'title'));
    }

    /**
     * Shows a single GedcomFamily. 
     * @param int $id
     */
    public function getShow($id)
    {
        $family = GedcomFamily::findOrFail($id);
        $husband = $family->husband;
        $wife = $family->wife;
        $this->layout->content = View::make('gedcom/families/detail', compact('family', 'husband', 'wife'));
    }

    /**
     * Show a list of all the GedcomFamilies formatted for Datatables.
     * @return Datatables JSON
     */
    public function getData()
    {
        $families = GedcomFamily::leftJoin('gedcoms AS g', 'families.gedcom_id', '=', 'g.id')
                ->leftJoin('individuals AS h', 'families.indi_id_husb', '=', 'h.id')
                ->leftJoin('individuals AS w', 'families.indi_id_wife', '=', 'w.id')
                ->select(array('g.file_name',
            'families.gedcom_id', 'families.gedcom_key', 'families.id',
            'families.indi_id_husb', 'families.indi_id_wife',
            'h.gedcom_key AS hgk', 'w.gedcom_key AS wgk'));
        return Datatables::of($families)
                        ->edit_column('file_name', '{{ HTML::link("gedcoms/show/" . $gedcom_id, $file_name) }}')
                        ->edit_column('gedcom_key', '{{ HTML::link("families/show/" . $id, $gedcom_key) }}')
                        ->edit_column('hgk', '{{ $indi_id_husb ? HTML::link("individuals/show/" . $indi_id_husb, $hgk) : "" }}')
                        ->edit_column('wgk', '{{ $indi_id_wife ? HTML::link("individuals/show/" . $indi_id_wife, $wgk) : "" }}')
                        ->remove_column('id')
                        ->remove_column('gedcom_id')
                        ->remove_column('indi_id_husb')
                        ->remove_column('indi_id_wife')
                        ->make();
    }

    /**
     * Show a list of all the GedcomEvents for the given GedcomFamily formatted for Datatables.
     * @return Datatables JSON
     */
    public function getEvents($id)
    {
        $events = GedcomEvent::select(array('event', 'date', 'place'))->where('fami_id', $id);
        return Datatables::of($events)->make();
    }

}
