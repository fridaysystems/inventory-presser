(()=>{"use strict";const e=window.wp.blocks,o=window.wp.i18n,i=window.wp.components,t=window.wp.coreData,n=window.wp.blockEditor,s=window.ReactJSXRuntime,d=JSON.parse('{"UU":"inventory-presser/last-modified"}');(0,e.getBlockType)(d.UU)||(0,e.registerBlockType)(d.UU,{edit:function({isSelected:e,context:d}){const{postType:r}=d,[p]=(0,t.useEntityProp)("postType",r,"meta"),l=(0,n.useBlockProps)();return e?(0,s.jsx)("div",{...l,children:(0,s.jsx)(i.TextControl,{label:(0,o.__)("Last Modified","inventory-presser"),value:p[invp_blocks.meta_prefix+"last_modified"],readOnly:"readonly"})}):(0,s.jsxs)("div",{...l,children:[" ",p[invp_blocks.meta_prefix+"last_modified"]," "]})}})})();