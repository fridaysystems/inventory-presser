(()=>{"use strict";const e=window.wp.blocks,o=window.wp.i18n,n=window.wp.components,r=window.wp.coreData,t=window.wp.blockEditor,p=window.ReactJSXRuntime,s=JSON.parse('{"UU":"inventory-presser/msrp"}');(0,e.getBlockType)(s.UU)||(0,e.registerBlockType)(s.UU,{edit:function({isSelected:e,context:s}){const{postType:i}=s,[c,w]=(0,r.useEntityProp)("postType",i,"meta"),l=(0,t.useBlockProps)();return e?(0,p.jsx)("div",{...l,children:(0,p.jsx)(n.TextControl,{label:(0,o.__)("MSRP","inventory-presser"),value:c[invp_blocks.meta_prefix+"msrp"],onChange:e=>w({...c,[invp_blocks.meta_prefix+"msrp"]:e.replace(/[^0-9\.]/g,"")})})}):(0,p.jsxs)("div",{...l,children:[" ",invpFormatCurrency(c[invp_blocks.meta_prefix+"msrp"])," "]})}})})();