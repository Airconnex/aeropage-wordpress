import axios from "axios";
import _ from "lodash/array";
// var _ = require("lodash/array");

export const processMedia = async ({
  responseData,
  setOpenMediaModal,
  setTotalMedia,
  isMediaCancelled,
  setCurrentMedia
}) => {
  console.log({responseData})
  if(responseData?.media?.length > 0){
    setOpenMediaModal(true);
    setTotalMedia(responseData?.media?.length);

    for(let i = 0; i < responseData?.media?.length; i += 1){
      //If the media modal is closed (cancelled), we will not proceed with the downloading
      if(isMediaCancelled.current > 0){
        isMediaCancelled.current = 0;
        break;
      }
      
      const media = responseData?.media[i];
      const index = i + 1;
      setCurrentMedia({ ...media, index: index });
      // console.log("LOOPING: ", media?.record_post_id, isMediaCancelled);
      
      console.log({ acf_field: responseData?.acf_media_fields })

      let params = new URLSearchParams();
      params.append("action", "aeropageMediaDownload");  
      params.append("media", JSON.stringify(media));
      params.append("acf_image_fields", JSON.stringify(responseData?.acf_media_fields ?? []));

      await axios.post(MYSCRIPT.ajaxUrl, params)
        .then(res => {})//console.log(res))
        .catch(err => {
          alert(err?.response?.data?.message ?? err?.message);
        });
    }

    await sleep(3000);
    setOpenMediaModal(false);
    isMediaCancelled.current = 0;
  }
}

export const sleep = (ms) => {
  return new Promise(resolve => setTimeout(resolve, ms));
}

export const processSync = async ({
  token,
  postID,
  setOpenSyncRecordModal,
  setTotalPosts,
  setCurrentPosts,
  setResponse = null,
  response = null
}) => {
  //Get the token
  const tokenResponse = await fetch("https://tools.aeropage.io/api/token/" + token, { redirect: "follow" })
    .then((responseAP) => responseAP.json())
    .then((data) => data);

  //If there is an error in the response
  if(tokenResponse?.status === "error") {
    alert(tokenResponse?.status?.message ?? tokenResponse?.message);
    console.error(tokenResponse);
    return tokenResponse;
  }

  console.log({ tokenResponse });
  const records = tokenResponse?.records ?? [];
  const fields = tokenResponse?.fields;
  const status = tokenResponse?.status;
  const chunked = _.chunk(records, 100);
  
  setTotalPosts(records?.length);
  let currentTotal = 0;
  const responseMedia = {};

  //Send the records per batch. Also include flags to tell the backend not to perform API calls.
  for(let i = 0; i < chunked?.length; i += 1){
    let params = new URLSearchParams();
    params.append("action", "aeropageSyncPosts");  
    params.append("id", postID);
    params.append("apiData", JSON.stringify({
      status,
      fields,
      records: chunked[i]
    }));
    params.append("firstBatch", i === 0 ? 1 : 0);
    params.append("noCall", 1);
    currentTotal += (chunked?.[i]?.length ?? 0)
    setCurrentPosts(currentTotal);
    const responseData = await axios.post(MYSCRIPT.ajaxUrl, params).then(function (responseAP) {
      console.log("INSIDE PROMISE: ", { response })
      if(response){
        const b = [...response];
        const a = b?.find(re => re.ID === postID);

        if(a){
          a.sync_message = responseAP?.data?.message;
        }

        console.log({CARDS: a, setResponse, b});

        setResponse && setResponse(b);
      }
      console.log({ responseAP })
      return responseAP?.data;
    })
      .catch(err => {
        console.error(err)
        alert(`Server Error: ${err?.response?.data?.message ?? err?.message}`);
        return null;
      });

    if(!responseData){
      setTotalPosts(null);
      setCurrentPosts(null);
      return null;
    }

    responseMedia["media"] = [
      ...(responseMedia["media"] ?? []),
      ...(responseData["media"] ?? [])
    ];
    responseMedia["acf_media_fields"] = {
      ...(responseMedia["acf_media_fields"] ?? {}),
      ...(responseData["acf_media_fields"] ?? [])
    }
    responseMedia["sync_time"] = responseData["sync_time"] ?? 0;
    responseMedia["status"] = responseData["status"];
  }

  setTotalPosts(null);
  setCurrentPosts(null);
  return responseMedia;
}