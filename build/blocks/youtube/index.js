(()=>{"use strict";const e=window.wp.blocks,t=window.wp.components,o=window.wp.coreData,i=window.wp.data,n=window.wp.blockEditor,s=window.ReactJSXRuntime,d=JSON.parse('{"UU":"inventory-presser/youtube"}');(0,e.getBlockType)(d.UU)||(0,e.registerBlockType)(d.UU,{edit:function({isSelected:e}){const d=(0,i.useSelect)((e=>e("core/editor").getCurrentPostType()),[]),[r,p]=(0,o.useEntityProp)("postType",d,"meta"),u=(0,n.useBlockProps)();return e?(0,s.jsx)("div",{...u,children:(0,s.jsx)(t.TextControl,{label:"YouTube Video ID",value:r[invp_blocks.meta_prefix+"youtube"],onChange:e=>p({...r,[invp_blocks.meta_prefix+"youtube"]:e})})}):(wp.apiRequest({url:wp.media.view.settings.oEmbedProxyUrl,data:{url:"https://www.youtube.com/watch?v="+r[invp_blocks.meta_prefix+"youtube"]},type:"GET",dataType:"json",context:this}).done((function(e){document.getElementById(u.id+"-oembed").innerHTML=e.html})),(0,s.jsxs)("div",{...u,children:[" ",(0,s.jsxs)("div",{id:u.id+"-oembed",children:[" ",r[invp_blocks.meta_prefix+"youtube"]]})]}))}})})();