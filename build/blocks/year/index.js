(()=>{"use strict";const e=window.wp.blocks,o=window.wp.i18n,n=window.wp.components,t=window.wp.coreData,r=window.wp.blockEditor,i=window.ReactJSXRuntime,s=JSON.parse('{"UU":"inventory-presser/year"}');(0,e.getBlockType)(s.UU)||(0,e.registerBlockType)(s.UU,{edit:function({isSelected:e,context:s}){const{postType:p}=s,[c,a]=(0,t.useEntityProp)("postType",p,"meta"),w=(0,r.useBlockProps)();return e?(0,i.jsx)("div",{...w,children:(0,i.jsx)(n.TextControl,{label:(0,o.__)("Year","inventory-presser"),value:c[invp_blocks.meta_prefix+"year"],onChange:e=>a({...c,[invp_blocks.meta_prefix+"year"]:e.replace(/[^0-9]/g,"")})})}):(0,i.jsxs)("div",{...w,children:[" ",c[invp_blocks.meta_prefix+"year"]," "]})}})})();