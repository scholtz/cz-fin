'use strict';
import $ from 'jquery'
window.jQuery = $;
window.$ = $;

import { findIconDefinition, library, icon } from '@fortawesome/fontawesome-svg-core'
import { 
  faSearch,
  faDownload,
  faFolder,
  faCheck,
  faTimes,
  faQuestionCircle,
  faEdit
 } from '@fortawesome/free-solid-svg-icons'

library.add(
  faSearch,
  faDownload,
  faFolder,
  faCheck,
  faTimes,
  faQuestionCircle,
  faEdit
)

    
$(function(){
    for(let index in library){
        if(index === undefined || index === null) continue;
        
        for(let namespace in library[index]){
            if(namespace === undefined || namespace === null) continue;
            
            for(let iconname in library[index][namespace]){
                //console.log(namespace,iconname);
                
                let icon1 = findIconDefinition({ prefix: namespace, iconName: iconname });
                if(icon1 === undefined ) console.log("0x01 icon not found",namespace, iconname );

                let icon2 = icon(icon1);
                if(icon2 === undefined  || icon2.node === undefined ) console.log("0x02 icon not found",namespace, iconname );
                //console.log(index, namespace, iconname, icon2.node, "."+namespace+".fa-"+iconname+"", $("."+namespace+".fa-"+iconname+"").length);
                $("."+namespace+".fa-"+iconname+"").replaceWith(icon2.node);
                $(".fa.fa-"+iconname+"").html(icon2.node);
                /**/
            }
        }
    }
});
/**/