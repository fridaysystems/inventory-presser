(()=>{"use strict";const e=window.wp.blocks,i=window.wp.i18n,o=window.wp.components,n=window.wp.coreData,r=window.wp.blockEditor,t=window.ReactJSXRuntime,p=JSON.parse('{"UU":"inventory-presser/price"}');(0,e.getBlockType)(p.UU)||(0,e.registerBlockType)(p.UU,{edit:function({isSelected:e,context:p}){const{postType:c}=p,[s,w]=(0,n.useEntityProp)("postType",c,"meta"),l=(0,r.useBlockProps)();return e?(0,t.jsx)("div",{...l,children:(0,t.jsx)(o.TextControl,{label:(0,i.__)("Price","inventory-presser"),value:s[invp_blocks.meta_prefix+"price"],onChange:e=>w({...s,[invp_blocks.meta_prefix+"price"]:e.replace(/[^0-9\.]/g,"")})})}):(0,t.jsxs)("div",{...l,children:[" ",invpFormatCurrency(s[invp_blocks.meta_prefix+"price"])," "]})}})})();