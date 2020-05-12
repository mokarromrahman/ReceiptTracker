package com.example.receipttracker;

import android.os.AsyncTask;
import android.util.Log;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.lang.ref.WeakReference;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.Hashtable;
import java.util.Map;
import java.util.TreeMap;

public class FindCountriesAsync extends AsyncTask<Void, Void, Map> {

    private WeakReference<MainActivity> mainActivityWeakReference;

    Map<String, String> myCountries;

    FindCountriesAsync(MainActivity mainActivity)
    {
        mainActivityWeakReference = new WeakReference<MainActivity>(mainActivity);
    }

    //Initialize the myCountries Map
    @Override
    protected void onPreExecute() {
        super.onPreExecute();

        myCountries = new Hashtable<>();
    }

    @Override
    protected Map doInBackground(Void... voids) {
        try {
            URL url = new URL("https://parseapi.back4app.com/classes/Continentscountriescities_Country?count=1&limit=250&order=name&keys=name");
            HttpURLConnection urlConnection = (HttpURLConnection)url.openConnection();
            urlConnection.setRequestProperty("X-Parse-Application-Id", "Ba2uRdWlbvbzwellUA7gTmmDEZf6bRPrXpnd8VaO"); // This is your app's application id
            urlConnection.setRequestProperty("X-Parse-REST-API-Key", "jxCa0OM9D0yd1S89ysT1Fjo2tkfJxorvFg3L36RC"); // This is your app's REST API key
            try {
                BufferedReader reader = new BufferedReader(new InputStreamReader(urlConnection.getInputStream()));
                StringBuilder stringBuilder = new StringBuilder();
                String line;
                while ((line = reader.readLine()) != null) {
                    stringBuilder.append(line);
                }
                //Parse the data from the API using JSON
                JSONObject data = new JSONObject(stringBuilder.toString()); // Here you have the data that you need
                JSONArray results =  data.getJSONArray("results");
                //Log.d("Objects in data", data.names().toString(2));

                for(Integer i = 0; i < results.length(); ++i)
                {
                    //Log.d("Reading JSON Results", results.getJSONObject(i).get("name").toString());
                    myCountries.put(results.getJSONObject(i).get("name").toString(), results.getJSONObject(i).get("objectId").toString());
                }

                //sort the dictionary
                myCountries = new TreeMap<>(myCountries);
            }
            finally {
                urlConnection.disconnect();
            }
        } catch (Exception e) {
            Log.e("Something went wrong", e.toString());
        }

        MainActivity mainActivity = mainActivityWeakReference.get();

        if(mainActivity == null || mainActivity.isFinishing()) {
            return myCountries;
        }

        mainActivity._dictCountries = new Hashtable<>(myCountries);
        Log.d("AsyncClass: ", "I'm in AsyncClass first");
        return myCountries;
    }

    @Override
    protected void onPostExecute(Map map) {
        super.onPostExecute(map);


    }
}
