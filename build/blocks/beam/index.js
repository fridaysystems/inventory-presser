(()=>{"use strict";const e=window.wp.blocks,o=window.wp.components,t=window.wp.coreData,i=window.wp.data,n=window.wp.blockEditor,s=window.ReactJSXRuntime,r=JSON.parse('{"UU":"inventory-presser/beam"}');(0,e.getBlockType)(r.UU)||(0,e.registerBlockType)(r.UU,{edit:function({isSelected:e}){const r=(0,i.useSelect)((e=>e("core/editor").getCurrentPostType()),[]),[p,c]=(0,t.useEntityProp)("postType",r,"meta"),a=(0,n.useBlockProps)();return e?(0,s.jsx)("div",{...a,children:(0,s.jsx)(o.TextControl,{label:"Beam",value:p[invp_blocks.meta_prefix+"beam"],onChange:e=>c({...p,[invp_blocks.meta_prefix+"beam"]:e})})}):(0,s.jsxs)("div",{...a,children:[" ",p[invp_blocks.meta_prefix+"beam"]," "]})}})})();