(()=>{"use strict";const e=window.wp.blocks,o=window.wp.components,t=window.wp.coreData,i=window.wp.data,s=window.wp.blockEditor,d=window.ReactJSXRuntime,n=JSON.parse('{"UU":"inventory-presser/last-modified"}');(0,e.getBlockType)(n.UU)||(0,e.registerBlockType)(n.UU,{edit:function({isSelected:e}){const n=(0,i.useSelect)((e=>e("core/editor").getCurrentPostType()),[]),[r,l]=(0,t.useEntityProp)("postType",n,"meta"),c=(0,s.useBlockProps)();return e?(0,d.jsx)("div",{...c,children:(0,d.jsx)(o.TextControl,{label:"Last Modified",value:r[invp_blocks.meta_prefix+"last_modified"],readOnly:"readonly"})}):(0,d.jsxs)("div",{...c,children:[" ",r[invp_blocks.meta_prefix+"last_modified"]," "]})}})})();