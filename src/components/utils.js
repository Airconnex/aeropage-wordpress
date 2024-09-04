export const convertToSlug = (text) => {
  //OLD METHOD
  // return text
  //   .toLowerCase()
  //   .replace(/ /g,'-')
  //   .replace(/[^\w-]+/g,'');

  if (text) {
    return text
      .toLowerCase()
      .replace(/[^\w ]+/g, "")
      .replace(/ +/g, "-");
  } else {
    return "";
  }
};

export const fetchToken = async (token) => {
  let url = `https://tools.aeropage.io/api/token/${token}`;

  if(token?.split("-").length > 1){
    url = `https://api.aeropage.io/api/v5/tools/connector/${token.split("-")[1]}`;
  }

  //Get the token
  return fetch(url, { redirect: "follow" })
      .then((responseAP) => responseAP.json())
}