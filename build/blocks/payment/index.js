(()=>{"use strict";const e=window.wp.blocks,n=window.wp.i18n,t=window.wp.components,o=window.wp.coreData,i=(window.wp.data,window.wp.blockEditor),p=window.ReactJSXRuntime,r=JSON.parse('{"UU":"inventory-presser/payment"}');(0,e.getBlockType)(r.UU)||(0,e.registerBlockType)(r.UU,{edit:function({isSelected:e,context:r}){const{postType:s}=r,[c,w]=(0,o.useEntityProp)("postType",s,"meta"),a=(0,i.useBlockProps)();return e?(0,p.jsx)("div",{...a,children:(0,p.jsx)(t.TextControl,{label:(0,n.__)("Payment","inventory-presser"),value:c[invp_blocks.meta_prefix+"payment"],onChange:e=>w({...c,[invp_blocks.meta_prefix+"payment"]:e})})}):(0,p.jsxs)("div",{...a,children:[" ",invpFormatCurrency(c[invp_blocks.meta_prefix+"payment"])," "]})}})})();