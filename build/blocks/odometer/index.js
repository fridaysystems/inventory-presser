(()=>{"use strict";const e=window.wp.blocks,o=window.wp.components,t=window.wp.coreData,r=window.wp.data,n=window.wp.blockEditor,i=window.ReactJSXRuntime,s=JSON.parse('{"UU":"inventory-presser/odometer"}');(0,e.getBlockType)(s.UU)||(0,e.registerBlockType)(s.UU,{edit:function({isSelected:e}){const s=(0,r.useSelect)((e=>e("core/editor").getCurrentPostType()),[]),[c,p]=(0,t.useEntityProp)("postType",s,"meta"),d=(0,n.useBlockProps)();return e?(0,i.jsx)(i.Fragment,{children:(0,i.jsx)(o.TextControl,{label:"Odometer",value:c[invp_blocks.meta_prefix+"odometer"],onChange:e=>p({...c,[invp_blocks.meta_prefix+"odometer"]:e})})}):(0,i.jsxs)("div",{...d,children:[" ",Number(c[invp_blocks.meta_prefix+"odometer"]).toLocaleString()+" "+invp_blocks.odometer_units," "]})}})})();